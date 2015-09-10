# PHP Background Worker Demo Application for Heroku

This application demonstrates the implementation of a very simple worker process using RabbitMQ on Heroku. The application will accept text through a form, pass it to an external service to remove any profanity, and then store the "censored" message in a database for displaying on the page. Because the performance of the call to the external "censoring" service depends on the performance of that API, this task will be carried out asynchronously in the background by a worker process to ensure users never experience slow response times on the site itself.

The moving parts:

* `www/index.php` to serve a front-end using Silex that allows submission of new text and displays existing submissions;
* `bin/worker.php` to process jobs: removing profanity from submitted text using [Bomberman](https://bomberman.ikayzo.com)'s API;
* RabbitMQ to queue these jobs;
* Redis to store results.

For a full explanation of the individual components and more background information, please check out the [PHP Workers](https://devcenter.heroku.com/articles/php-workers) article on Heroku Dev Center.

To deploy this application to Heroku right now, you can use this button:

[![Deploy](https://www.herokucdn.com/deploy/button.png)](https://heroku.com/deploy)