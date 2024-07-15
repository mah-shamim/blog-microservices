To create a raw PHP microservice-based blog project, we'll develop two microservices: one for user management and one for blog posts. We'll also set up a basic API gateway to route the requests to the appropriate services.

### Project Structure

```
php-microservices/
    user-service/
        public/
        src/
        vendor/
        .env
        composer.json
        index.php
    post-service/
        public/
        src/
        vendor/
        .env
        composer.json
        index.php
    api-gateway/
        public/
        src/
        vendor/
        .env
        composer.json
        index.php
    docker-compose.yml
```

### Step-by-Step Guide

1. **Setup the User Service**: This service will handle user registration and authentication.

   **a. Create `composer.json` in `user-service/`:**
    ```json
    {
        "require": {
            "slim/slim": "^3.0",
            "slim/psr7": "^1.4",
            "firebase/php-jwt": "^5.0"
        }
    }
    ```

   **b. Create `index.php` in `user-service/`:**
    ```php
    <?php

    require 'vendor/autoload.php';

    use Slim\Factory\AppFactory;

    $app = AppFactory::create();

    $app->post('/register', function ($request, $response) {
        $data = $request->getParsedBody();
        // Registration logic here (e.g., save to database)
        return $response->withJson(['message' => 'User registered successfully']);
    });

    $app->post('/login', function ($request, $response) {
        $data = $request->getParsedBody();
        // Authentication logic here (e.g., verify credentials and generate JWT)
        return $response->withJson(['token' => 'JWT_TOKEN']);
    });

    $app->run();
    ```

2. **Setup the Post Service**: This service will manage blog posts.

   **a. Create `composer.json` in `post-service/`:**
    ```json
    {
        "require": {
            "slim/slim": "^3.0",
            "slim/psr7": "^1.4"
        }
    }
    ```

   **b. Create `index.php` in `post-service/`:**
    ```php
    <?php

    require 'vendor/autoload.php';

    use Slim\Factory\AppFactory;

    $app = AppFactory::create();

    $app->get('/posts', function ($request, $response) {
        // Fetch posts from database
        $posts = []; // Replace with actual data fetching logic
        return $response->withJson($posts);
    });

    $app->post('/posts', function ($request, $response) {
        $data = $request->getParsedBody();
        // Save post to database
        return $response->withJson(['message' => 'Post created successfully']);
    });

    $app->run();
    ```

3. **Setup API Gateway**: This will route requests to appropriate microservices.

   **a. Create `composer.json` in `api-gateway/`:**
    ```json
    {
        "require": {
            "slim/slim": "^3.0",
            "slim/psr7": "^1.4",
            "guzzlehttp/guzzle": "^7.0"
        }
    }
    ```

   **b. Create `index.php` in `api-gateway/`:**
    ```php
    <?php

    require 'vendor/autoload.php';

    use Slim\Factory\AppFactory;
    use GuzzleHttp\Client;

    $app = AppFactory::create();
    $client = new Client();

    $app->any('/{path:.*}', function ($request, $response, $args) use ($client) {
        $path = $args['path'];
        $method = $request->getMethod();
        $data = $request->getParsedBody();

        if (strpos($path, 'user') === 0) {
            $url = 'http://user-service/' . $path;
        } elseif (strpos($path, 'post') === 0) {
            $url = 'http://post-service/' . $path;
        } else {
            return $response->withJson(['error' => 'Not Found'], 404);
        }

        $res = $client->request($method, $url, [
            'json' => $data,
            'headers' => $request->getHeaders()
        ]);

        $body = $res->getBody();
        $contents = $body->getContents();
        $body->close();

        $response->getBody()->write($contents);

        return $response->withStatus($res->getStatusCode())->withHeaders($res->getHeaders());
    });

    $app->run();
    ```

4. **Dockerize the Services**: Use Docker Compose to run the services.

   **a. Create `docker-compose.yml` in the root of the project:**
    ```yaml
    version: '3.8'

    services:
      user-service:
        build: ./user-service
        ports:
          - 8001:80
        environment:
          - DB_CONNECTION=mysql
          - DB_HOST=db
          - DB_PORT=3306
          - DB_DATABASE=user_service
          - DB_USERNAME=root
          - DB_PASSWORD=root
        depends_on:
          - db

      post-service:
        build: ./post-service
        ports:
          - 8002:80
        environment:
          - DB_CONNECTION=mysql
          - DB_HOST=db
          - DB_PORT=3306
          - DB_DATABASE=post_service
          - DB_USERNAME=root
          - DB_PASSWORD=root
        depends_on:
          - db

      api-gateway:
        build: ./api-gateway
        ports:
          - 8000:80
        depends_on:
          - user-service
          - post-service

      db:
        image: mysql:5.7
        environment:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: user_service
          MYSQL_DATABASE: post_service
        ports:
          - 3306:3306
    ```

   **b. Create Dockerfiles for each service in their respective directories:**

   For `user-service/Dockerfile`, `post-service/Dockerfile`, and `api-gateway/Dockerfile`:
    ```Dockerfile
    FROM php:7.4-cli

    COPY . /usr/src/myapp
    WORKDIR /usr/src/myapp

    RUN apt-get update && apt-get install -y libzip-dev \
        && docker-php-ext-install zip

    RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

    RUN composer install

    CMD ["php", "-S", "0.0.0.0:80", "-t", "public"]
    ```

### Download the Source Code

You can download the full source code from this GitHub repository: [PHP Microservices Blog Project](https://github.com/mah-shamim/blog-microservices).

(Note: Replace the link with your actual GitHub repository link if you create one.)

This setup provides a simple microservice architecture using raw PHP and Slim framework, with Docker for containerization. The API gateway routes requests to the appropriate microservices based on the URL path. Each microservice handles its own specific domain logic, such as user management and blog post management.