ARG LOCAL_PHP="8.0"

FROM wordpressdevelop/php:${LOCAL_PHP}-fpm

# Allow devcontainer/Codespaces to use www-data as the remote user instead of root.
RUN usermod --shell /bin/bash www-data
RUN touch /var/www/.bashrc
RUN chown -R www-data: /var/www/

# Install WP-CLI dependencies
RUN set -ex; \
	apt-get update; \
	apt-get install -y --no-install-recommends \
		less \
		virtual-mysql-client \
	;

# Install WP-CLI
RUN curl -L -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
	chmod +x /usr/local/bin/wp
ENV WP_CLI_ALLOW_ROOT=1

# Install nvm and node
ENV NODE_VERSION=16.15.0
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.1/install.sh | bash
ENV NVM_DIR="/root/.nvm"
RUN . "$NVM_DIR/nvm.sh" && nvm install $NODE_VERSION
RUN . "$NVM_DIR/nvm.sh" && nvm use $NODE_VERSION
RUN . "$NVM_DIR/nvm.sh" && nvm alias default v$NODE_VERSION
ENV PATH="/root/.nvm/versions/node/v$NODE_VERSION/bin/:$PATH"
RUN node --version
RUN npm --version

# Allow www-data user to use sudo without password
RUN adduser www-data sudo
RUN echo '%sudo ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers
