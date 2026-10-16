FROM php:8.2-cli

# Extensão para o PDO conversar com o MySQL (instalada uma vez, na construção da imagem)
RUN docker-php-ext-install pdo_mysql

# Composer e unzip, para instalar as dependências com: docker compose run --rm api composer install
RUN apt-get update && apt-get install -y --no-install-recommends unzip && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
