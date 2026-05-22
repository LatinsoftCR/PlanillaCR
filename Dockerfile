FROM php:8.3-apache

# Actualizar el sistema e instalar dependencias iniciales necesarias (curl, gnupg2, etc.)
RUN apt-get update && apt-get install -y \
    curl \
    gnupg2 \
    apt-transport-https \
    unixodbc-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Descargar e instalar las claves oficiales y el repositorio de Microsoft SQL Server para Debian 12
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && curl -fsSL https://packages.microsoft.com/config/debian/12/prod.list | tee /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql18 mssql-tools18 \
    && echo 'export PATH="$PATH:/opt/mssql-tools18/bin"' >> ~/.bashrc \
    && rm -rf /var/lib/apt/lists/*

# Instalar y habilitar las extensiones sqlsrv y pdo_sqlsrv mediante PECL
RUN pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv

# Habilitar el módulo de reescritura de Apache (mod_rewrite) por si se usa en el ERP
RUN a2enmod rewrite

# Definir el directorio de trabajo
WORKDIR /var/www/html

# Copiar el código del proyecto
COPY . /var/www/html/

# Ajustar los permisos para que Apache (www-data) pueda servir los archivos
RUN chown -R www-data:www-data /var/www/html

# Exponer el puerto 80 estándar
EXPOSE 80
