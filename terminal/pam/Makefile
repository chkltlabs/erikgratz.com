CC ?= gcc

pam_terminal.so: pam_terminal.c
	$(CC) -fPIC -shared -O2 -Wall -Wextra -o $@ $< -lpam

.PHONY: pam_terminal.so
