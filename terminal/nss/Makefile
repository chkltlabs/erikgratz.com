CC ?= gcc
CFLAGS ?= -O2 -Wall -Wextra -fPIC
LIBDIR ?= /lib/x86_64-linux-gnu

libnss_terminal.so.2: nss_terminal.c
	$(CC) $(CFLAGS) -shared -Wl,-soname,libnss_terminal.so.2 -o $@ $<

install: libnss_terminal.so.2
	install -m 644 libnss_terminal.so.2 $(LIBDIR)/libnss_terminal.so.2

.PHONY: install
