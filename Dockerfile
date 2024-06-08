# Usar una imagen oficial de PHP con Apache
FROM php:8.2-apache

# Instalar extensiones de PHP necesarias para Laravel
RUN docker-php-ext-install pdo pdo_mysql


# Instalar utilidades de red y cliente MySQL
RUN apt-get update && apt-get install -y \
    iputils-ping \
    dnsutils \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*


# Copiar el contenido del repositorio al contenedor
COPY . /var/www/html

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Instalar dependencias de Composer
RUN apt-get update && apt-get install -y unzip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install


# Configurar permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Exponer el puerto 8000
EXPOSE 8000

# Comando para iniciar el servidor
CMD php artisan serve --host=0.0.0.0 --port=8000
