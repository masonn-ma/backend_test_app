#!/usr/bin/env bash

set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

echo "[1/8] Installing system packages..."
sudo apt update
sudo apt install -y \
  git \
  curl \
  unzip \
  ca-certificates \
  gnupg \
  lsb-release \
  php \
  php-cli \
  php-mbstring \
  php-intl \
  php-xml \
  php-curl \
  php-zip \
  docker.io \
  docker-compose-v2

echo "[2/8] Enabling Docker service..."
sudo systemctl enable docker
sudo systemctl start docker

if ! groups "$USER" | grep -q '\bdocker\b'; then
  echo "[3/8] Adding $USER to docker group..."
  sudo usermod -aG docker "$USER"
  echo "You were added to the docker group. Re-login is recommended after setup."
else
  echo "[3/8] Docker group membership already configured."
fi

COMPOSER_CMD="composer"

if ! command -v composer >/dev/null 2>&1; then
  echo "[4/8] Installing Composer..."
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  if [[ -w /usr/local/bin ]]; then
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
  else
    if command -v sudo >/dev/null 2>&1; then
      sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    else
      mkdir -p "$HOME/.local/bin"
      php composer-setup.php --install-dir="$HOME/.local/bin" --filename=composer
      export PATH="$HOME/.local/bin:$PATH"
      COMPOSER_CMD="$HOME/.local/bin/composer"
      echo "Composer installed to $HOME/.local/bin/composer"
    fi
  fi
  rm -f composer-setup.php
else
  echo "[4/8] Composer already installed."
fi

if command -v composer >/dev/null 2>&1; then
  COMPOSER_CMD="composer"
elif [[ -x "$HOME/.local/bin/composer" ]]; then
  COMPOSER_CMD="$HOME/.local/bin/composer"
  export PATH="$HOME/.local/bin:$PATH"
else
  echo "Composer installation failed."
  exit 1
fi

echo "[5/8] Installing PHP dependencies..."
cd "$PROJECT_DIR"
"$COMPOSER_CMD" install

echo "[6/8] Preparing app local config..."
if [[ ! -f "$PROJECT_DIR/config/app_local.php" ]]; then
  cp "$PROJECT_DIR/config/app_local.example.php" "$PROJECT_DIR/config/app_local.php"
fi

echo "[7/8] Configuring vm.max_map_count for Elasticsearch..."
sudo sysctl -w vm.max_map_count=262144
echo "vm.max_map_count=262144" | sudo tee /etc/sysctl.d/99-elasticsearch.conf >/dev/null
sudo sysctl --system >/dev/null

echo "[8/8] Starting MongoDB, Elasticsearch, and Kibana..."
if docker info >/dev/null 2>&1; then
  docker compose -f "$PROJECT_DIR/docker-compose.yml" up -d
else
  sudo docker compose -f "$PROJECT_DIR/docker-compose.yml" up -d
fi

echo ""
echo "Setup complete."
echo "Next steps:"
echo "1) If docker commands fail without sudo, log out and log back in once."
echo "2) Start app: cd $PROJECT_DIR && bin/cake server -p 8765"
echo "3) Open: http://localhost:8765"
