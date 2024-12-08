## Setup

- Clone this repository.
- Create a .env file, copy the content present in .env.example and replace ```DATABASE_URL``` with actual connection string
- Configure port number in docker-compose.yml file.
- Run this command to create containers ```docker compose up --build``` (For windows start docker desktop before running this command).
- After the containers are successfully created, run this command in terminal  ```php bin/console doctrine:migrations:migrate``` to run migration files
- After successful migrations run this command to populate the DB ```php bin/console app:ArticleSeed```
- After DB is populated successfully hit this url http://localhost:8000/api/articles
- To export data hit this url http://localhost:8000/api/articles/export?[filters]&format=csv/excel
- Filters which needs to be passed are ```author_name, title, summary, created_at```   