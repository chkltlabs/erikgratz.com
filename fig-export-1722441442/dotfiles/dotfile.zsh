alias -- pint='./vendor/bin/pint'

alias -- which-command='whence'
alias -- run-help='man'
alias -- refresh='source ~/.zshrc'
alias -- list-alias='cat ~/.zshrc'
alias -- selfhost='ssh 192.168.1.10 -l erik'
export SECURITYSESSIONID="186b3"
export P9K_SSH="0"
PATH="$PATH:"'/Users/a/.composer/vendor/bin'
alias -- work='cd ~/Documents/Pocketnest/pocketnest-web'
export LC_FIG_SET_PARENT="e21d5734-5a1b-49dc-b1bd-a5cc593e4aab"
alias -- work2='cd ~/Documents/Pocketnest/pocketnest-web-2'
alias -- site='cd ~/Code/erikgratz.com ; code .'
alias -- gs='git status'
PATH="$PATH:"'/Users/erikgratz/.local/bin'
alias -- sail='[ -f sail ] && bash sail || bash vendor/bin/sail'
export LaunchInstanceID="2EB704C4-28A3-40A7-8D9E-F8325436EFF8"
export HISTCONTROL="ignoreboth"
PATH="$PATH:"'/Users/a/.composer/vendor/bin'
alias -- ga='git add'
alias -- wix='cd ~/Code/wix-client ; code .'
export NO_COLOR="1"
PATH="$PATH:"'/Users/erikgratz/.fig/bin'
export P9K_TTY="old"
export _P9K_TTY="/dev/ttys003"
export HISTFILE="/Users/erikgratz/.zsh_sessions/843CE0DA-ADD2-408F-BF1C-CB6A2C67F659.historynew"
### zsh-autosuggestions
if [ -d '/Users/erikgratz/.local/share/fig/plugins/zsh-autosuggestions' ]; then

source '/Users/erikgratz/.local/share/fig/plugins/zsh-autosuggestions/zsh-autosuggestions.zsh'
fi

### zsh-syntax-highlighting
if [ -d '/Users/erikgratz/.local/share/fig/plugins/zsh-syntax-highlighting' ]; then

source '/Users/erikgratz/.local/share/fig/plugins/zsh-syntax-highlighting/zsh-syntax-highlighting.zsh'
fi

### z.lua
if [ -d '/Users/erikgratz/.local/share/fig/plugins/z.lua' ]; then
_ZL_CMD="lua"
eval "$(lua /Users/erikgratz/.local/share/fig/plugins/z.lua/z.lua --init zsh)"
fi

### powerlevel10k
if [ -d '/Users/erikgratz/.local/share/fig/plugins/powerlevel10k' ]; then
if [[ -r "${XDG_CACHE_HOME:-$HOME/.cache}/p10k-instant-prompt-${(%):-%n}.zsh" ]]; then
  source "${XDG_CACHE_HOME:-$HOME/.cache}/p10k-instant-prompt-${(%):-%n}.zsh"
fi

source '/Users/erikgratz/.local/share/fig/plugins/powerlevel10k/powerlevel10k.zsh-theme'
[[ ! -f ~/.p10k.zsh ]] || source ~/.p10k.zsh
fi
