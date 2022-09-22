# Installation

This requires [Docker](https://www.docker.com/) to run.

Install the dependencies and follow the steps.

### Step(1) : Clone the repository
Open your terminal and run the following command
```sh
git clone <your-repository-link>
```
This  will clone your project & create a new directory "card".

### Step(2) : Generate the .env file
Open your terminal and change the terminal directory to card >
Now run the following command 
```sh
cp .env.example .env
```
This  will create new .env file and copy the content from .env.example to .env
(You can manually create .env file & copy-paste the content from .env.example file, if above command not works)
feel free to change DB_DATABASE, DB_USERNAME, DB_PASSWORD as per your choice.

### Step(3) : Build an image of your project
```sh
docker-compose build
```

### Step(4) : Run your project as docker container
```sh
docker-compose up
```
congrats! Your project is up & running.

### Step(5) : Interacting with container
Check the container name of your project (Possible it should be "flashcardplay" get the container id) so run
```sh
docker exec -it <container-id> sh 
```
This will bring you to the terminal of your project inside docker container.
NB**Just to check the tables have been created run:
```php artisan migrate
```
If you have nothing to migrate the message should show  :INFO  Nothing to migrate.

### Finally : Play with Flashcards CLI
```sh
php artisan flashcard:interactive
```

If the project does not work 
Clone the repo in your laravel dev environment adjust the .env file then run the migrations and you'll be able to run Flashcards 
