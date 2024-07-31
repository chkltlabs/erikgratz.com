alias 'pint' './vendor/bin/pint'

alias 'which-command' 'whence'
alias 'run-help' 'man'
alias 'refresh' 'source ~/.zshrc'
alias 'list-alias' 'cat ~/.zshrc'
alias 'selfhost' 'ssh 192.168.1.10 -l erik'
set -x SECURITYSESSIONID "186b3"
set -x P9K_SSH "0"
set PATH '/Users/a/.composer/vendor/bin' $PATH
alias 'work' 'cd ~/Documents/Pocketnest/pocketnest-web'
set -x LC_FIG_SET_PARENT "e21d5734-5a1b-49dc-b1bd-a5cc593e4aab"
alias 'work2' 'cd ~/Documents/Pocketnest/pocketnest-web-2'
alias 'site' 'cd ~/Code/erikgratz.com ; code .'
alias 'gs' 'git status'
set PATH '/Users/erikgratz/.local/bin' $PATH
alias 'sail' '[ -f sail ] && bash sail || bash vendor/bin/sail'
set -x LaunchInstanceID "2EB704C4-28A3-40A7-8D9E-F8325436EFF8"
set -x HISTCONTROL "ignoreboth"
set PATH '/Users/a/.composer/vendor/bin' $PATH
alias 'ga' 'git add'
alias 'wix' 'cd ~/Code/wix-client ; code .'
set -x NO_COLOR "1"
set PATH '/Users/erikgratz/.fig/bin' $PATH
set -x P9K_TTY "old"
set -x _P9K_TTY "/dev/ttys003"
set -x HISTFILE "/Users/erikgratz/.zsh_sessions/843CE0DA-ADD2-408F-BF1C-CB6A2C67F659.historynew"
### zsh-autosuggestions is not supported by fish
### zsh-syntax-highlighting is not supported by fish
### z.lua
if test -d '/Users/erikgratz/.local/share/fig/plugins/z.lua'
set _ZL_CMD "lua"
source '/Users/erikgratz/.local/share/fig/plugins/z.lua/init.fish'
eval "$(lua /Users/erikgratz/.local/share/fig/plugins/z.lua/z.lua --init fish)"
end

### powerlevel10k is not supported by fish