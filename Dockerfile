# Use the official PHP image
FROM php:8.2.0-fpm

# Install dependencies for PHP and Node.js
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    unzip \
    curl \
    git \
    libzip-dev \
    libmagickwand-dev # <-- Add this line for Imagick

# Install Imagick extension
RUN pecl install imagick && docker-php-ext-enable imagick # <-- Add this line for Imagick

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip

# Install Node.js
RUN curl -sL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs

# Update package sources and install Chromium
RUN apt-get update && apt-get install -y \
    apt-transport-https \
    ca-certificates \
    gnupg \
    wget \
    && wget -q -O - https://dl-ssl.google.com/linux/linux_signing_key.pub | apt-key add - \
    && echo "deb [arch=amd64] https://dl.google.com/linux/chrome/deb/ stable main" >> /etc/apt/sources.list.d/google-chrome.list \
    && apt-get update \
    && apt-get install -y google-chrome-stable

ENV PATH /node_modules/.bin:$PATH

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Add a new non-root user with the same UID and GID as the host user (usually 1000:1000)
# Replace 'myuser' with your preferred username
RUN addgroup --gid 1000 cassiopea && \
    adduser --disabled-password --gecos '' --uid 1000 --gid 1000 cassiopea

# Set the user to use when running the image
USER cassiopea

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
