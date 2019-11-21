FROM php:7.3.11-fpm

ENV DEBIAN_FRONTEND noninteractive

# Setup and install base system software
RUN echo "locales locales/locales_to_be_generated multiselect en_US.UTF-8 UTF-8" | debconf-set-selections \
    && echo "locales locales/default_environment_locale select en_US.UTF-8" | debconf-set-selections \
    && apt-get update && apt-get --yes --no-install-recommends install \
        locales ca-certificates curl vim nano unzip tar tzdata \
    && rm -rf /var/lib/apt/lists/*

# Configure php and install extensions
RUN mv "${PHP_INI_DIR}/php.ini-production" "${PHP_INI_DIR}/php.ini" \
    && docker-php-ext-install mysqli

# Get q2a
ENV SRC_DIR /opt/q2a/src/
WORKDIR /opt/q2a/

ARG Q2A_VERSION="1.8.3"
ENV Q2A_FILE_NAME "question2answer-${Q2A_VERSION}"
ENV Q2A_DOWNLOAD_URL "https://github.com/q2a/question2answer/releases/download/v${Q2A_VERSION}/${Q2A_FILE_NAME}.zip"

RUN curl -L ${Q2A_DOWNLOAD_URL} --output ${Q2A_FILE_NAME}.zip \
    && unzip ${Q2A_FILE_NAME}.zip \
    && rm ${Q2A_FILE_NAME}.zip \
    && mv ${Q2A_FILE_NAME} src

COPY files/qa-entrypoint.sh /usr/local/bin/qa-entrypoint
ENTRYPOINT ["qa-entrypoint"]
