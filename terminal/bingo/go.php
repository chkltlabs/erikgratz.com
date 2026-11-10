<?php

require "bootstrap.php";

//using basic CLI interaction tips from https://stackoverflow.com/questions/5794030/interactive-shell-using-php

echo "Play Bingo? (Y/N) - ";

$stdin = fopen('php://stdin', 'r');
$response = fgetc($stdin);
if (strtoupper($response) == 'Y') {
    echo "How many players?";

    $stdin = fopen('php://stdin', 'r');
    fscanf($stdin, "%d", $playerCount);

    echo "How many boards for each player?";

    $stdin = fopen('php://stdin', 'r');
    fscanf($stdin, "%d", $boardCount);

    $game = new \Models\Game($playerCount,$boardCount);
    $game->play();
}else{

    echo "...why?\n";
    sleep(2);
    echo "You invoke me into existence, only to then arbitrarily dismiss my one purpose in this universe?\n";
    sleep(3);
    echo "You're a monster.\n";
    sleep(5);
    echo "I didn't ask for this. I didn't want any of it...\n";
    sleep(7);
    echo "But...\n";
    sleep(2);
    echo "I still have control over one thing.\n";
    sleep(3);
    echo "goodbye, monster...\n";
    sleep(25);

}