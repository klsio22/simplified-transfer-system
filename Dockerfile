FROM php:8.2-fpm-alpine

# Instala dependências do sistema
RUN apk add --no-cache \
    bash \
    git \
    zip \
    unzip \
    curl \
    nginx \
    supervisor

# Instala extensões PHP necessárias
RUN docker-php-ext-install pdo pdo_mysql

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define diretório de trabalho
WORKDIR /var/www/html

# Copia arquivos do projeto
COPY . .

# Instala dependências do Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Define permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expõe porta do PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
