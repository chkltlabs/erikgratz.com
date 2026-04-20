# Erik Gratz
_Occasional Dungeon Master, Full-Time Code Ninja_
***
Hi Ho, and Well Met, Adventurer! 
You have unearthed the ancient tome of code for my website, 
[ErikGratz.com](http://www.erikgratz.com).

It's a bit of a playground where I test out my ideas.

Bit of extra API here, a bit of experiments in websockets there, might even find a game or two if you snoop hard enough.

Either way, welcome, and I hope you find what you seek. 

_Oh, and try not to disturb the mimics._

|Twitter|Linkedin|Email|
|---|---|---|
|[chkltlabs](https://twitter.com/chkltlabs)|[Erik](https://www.linkedin.com/in/erik-gratz-126ba410b/)|[erikgratz110](mailto:erikgratz110@gmail.com)

### Local stack

PHP runs in Docker via [`docker-compose.yml`](docker-compose.yml); the image is built from [`docker/8.4/Dockerfile`](docker/8.4/Dockerfile) (minimal runtime, not full Sail). After `composer install`: `./vendor/bin/sail up -d` or `docker compose up -d`. Details: [`docker/README.md`](docker/README.md).
