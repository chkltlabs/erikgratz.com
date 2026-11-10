#define _GNU_SOURCE
#include <errno.h>
#include <fcntl.h>
#include <nss.h>
#include <pwd.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>

/* Maps a login that is not a real account onto the terminal user.
 * OpenSSH stays on port 22. forge and root are never answered here.
 * Only sshd may ask, so other programs keep the normal passwd database.
 */

static int from_sshd(void) {
	char comm[32];
	int fd = open("/proc/self/comm", O_RDONLY);
	ssize_t n;
	if (fd < 0) {
		return 0;
	}
	n = read(fd, comm, sizeof comm - 1);
	close(fd);
	if (n <= 0) {
		return 0;
	}
	if (comm[n - 1] == '\n') {
		n--;
	}
	comm[n] = '\0';
	return strcmp(comm, "sshd") == 0
		|| strcmp(comm, "sshd-session") == 0
		|| strcmp(comm, "sshd-auth") == 0;
}

static int visitor_name(const char *name) {
	size_t i, n;
	if (name == NULL) {
		return 0;
	}
	if (name[0] == '\0') {
		return 1;
	}
	if (strcmp(name, "forge") == 0 || strcmp(name, "root") == 0) {
		return 0;
	}
	n = strlen(name);
	if (n > 32) {
		return 0;
	}
	for (i = 0; i < n; i++) {
		unsigned char c = (unsigned char) name[i];
		int ok = (c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z')
			|| (c >= '0' && c <= '9') || c == '_' || c == '-' || c == '.';
		if (!ok) {
			return 0;
		}
	}
	return 1;
}

static int terminal_ids(uid_t *uid, gid_t *gid) {
	FILE *file = fopen("/etc/passwd", "r");
	char line[4096];
	if (file == NULL) {
		return -1;
	}
	while (fgets(line, sizeof line, file) != NULL) {
		char *p;
		unsigned long parsed_uid, parsed_gid;
		if (strncmp(line, "terminal:", 9) != 0) {
			continue;
		}
		p = strchr(line, ':');
		if (p == NULL) {
			break;
		}
		p = strchr(p + 1, ':');
		if (p == NULL) {
			break;
		}
		parsed_uid = strtoul(p + 1, &p, 10);
		if (*p != ':') {
			break;
		}
		parsed_gid = strtoul(p + 1, &p, 10);
		fclose(file);
		if (parsed_uid == 0 || parsed_gid == 0) {
			return -1;
		}
		*uid = (uid_t) parsed_uid;
		*gid = (gid_t) parsed_gid;
		return 0;
	}
	fclose(file);
	return -1;
}

static enum nss_status fill(const char *name, uid_t uid, gid_t gid, struct passwd *pwd,
	char *buffer, size_t buflen, int *errnop) {
	const char *pass = "x";
	const char *gecos = "ErikGratz terminal";
	const char *dir = "/var/lib/erikgratz-terminal";
	const char *shell = "/usr/local/bin/erikgratz-terminal-shell";
	size_t nlen = strlen(name) + 1;
	size_t plen = strlen(pass) + 1;
	size_t glen = strlen(gecos) + 1;
	size_t dlen = strlen(dir) + 1;
	size_t slen = strlen(shell) + 1;
	char *p;
	if (buflen < nlen + plen + glen + dlen + slen) {
		*errnop = ERANGE;
		return NSS_STATUS_TRYAGAIN;
	}
	p = buffer;
	pwd->pw_name = p;
	memcpy(p, name, nlen);
	p += nlen;
	pwd->pw_passwd = p;
	memcpy(p, pass, plen);
	p += plen;
	pwd->pw_gecos = p;
	memcpy(p, gecos, glen);
	p += glen;
	pwd->pw_dir = p;
	memcpy(p, dir, dlen);
	p += dlen;
	pwd->pw_shell = p;
	memcpy(p, shell, slen);
	pwd->pw_uid = uid;
	pwd->pw_gid = gid;
	*errnop = 0;
	return NSS_STATUS_SUCCESS;
}

enum nss_status _nss_terminal_getpwnam_r(const char *name, struct passwd *pwd,
	char *buffer, size_t buflen, int *errnop) {
	uid_t uid;
	gid_t gid;
	const char *pwname = name;
	if (!from_sshd() || !visitor_name(name)) {
		*errnop = ENOENT;
		return NSS_STATUS_NOTFOUND;
	}
	if (terminal_ids(&uid, &gid) != 0) {
		*errnop = ENOENT;
		return NSS_STATUS_UNAVAIL;
	}
	if (name[0] == '\0') {
		pwname = "terminal";
	}
	return fill(pwname, uid, gid, pwd, buffer, buflen, errnop);
}

enum nss_status _nss_terminal_getpwuid_r(uid_t uid, struct passwd *pwd,
	char *buffer, size_t buflen, int *errnop) {
	uid_t want;
	gid_t gid;
	if (!from_sshd()) {
		*errnop = ENOENT;
		return NSS_STATUS_NOTFOUND;
	}
	if (terminal_ids(&want, &gid) != 0) {
		*errnop = ENOENT;
		return NSS_STATUS_UNAVAIL;
	}
	if (uid != want) {
		*errnop = ENOENT;
		return NSS_STATUS_NOTFOUND;
	}
	return fill("terminal", want, gid, pwd, buffer, buflen, errnop);
}
