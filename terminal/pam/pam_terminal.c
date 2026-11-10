#define PAM_SM_AUTH
#define PAM_SM_ACCOUNT
#define PAM_SM_SESSION

#include <security/pam_modules.h>
#include <grp.h>
#include <pwd.h>
#include <string.h>

static int terminal_member(pam_handle_t *pamh) {
	const char *user = NULL;
	struct passwd *pw;
	struct group *gr;

	if (pam_get_user(pamh, &user, NULL) != PAM_SUCCESS || user == NULL || user[0] == '\0') {
		return 0;
	}
	if (strcmp(user, "forge") == 0 || strcmp(user, "root") == 0) {
		return 0;
	}
	pw = getpwnam(user);
	gr = getgrnam("terminal");
	if (pw == NULL || gr == NULL) {
		return 0;
	}
	return pw->pw_gid == gr->gr_gid;
}

static int allow(pam_handle_t *pamh) {
	return terminal_member(pamh) ? PAM_SUCCESS : PAM_IGNORE;
}

PAM_EXTERN int pam_sm_authenticate(pam_handle_t *pamh, int flags, int argc, const char **argv) {
	(void) flags;
	(void) argc;
	(void) argv;
	return allow(pamh);
}

PAM_EXTERN int pam_sm_setcred(pam_handle_t *pamh, int flags, int argc, const char **argv) {
	(void) flags;
	(void) argc;
	(void) argv;
	return allow(pamh);
}

PAM_EXTERN int pam_sm_acct_mgmt(pam_handle_t *pamh, int flags, int argc, const char **argv) {
	(void) flags;
	(void) argc;
	(void) argv;
	return allow(pamh);
}

PAM_EXTERN int pam_sm_open_session(pam_handle_t *pamh, int flags, int argc, const char **argv) {
	(void) flags;
	(void) argc;
	(void) argv;
	return allow(pamh);
}

PAM_EXTERN int pam_sm_close_session(pam_handle_t *pamh, int flags, int argc, const char **argv) {
	(void) flags;
	(void) argc;
	(void) argv;
	return allow(pamh);
}
