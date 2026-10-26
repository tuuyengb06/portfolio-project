# Portfolio project

Website portfolio PHP/Apache, MySQL and optional Prometheus/Grafana and Loki/Promtail services.

## Requirements

- Docker Engine
- Docker Compose plugin

## Configuration

Copy `.env.example` to `.env`, then replace the sample database and Grafana passwords with strong local values. Do not commit `.env`.

## Start the website and database

```sh
docker compose up -d --build
```

- Portfolio: http://localhost:8090
- phpMyAdmin: http://localhost:8091

Initialize the example projects table when needed:

```sh
docker compose exec web php init.php
```

## Optional monitoring

Start Prometheus and Grafana alongside the website and database:

```sh
docker compose -f compose.yaml -f compose.monitoring.yaml up -d
```

- Prometheus: http://localhost:9090
- Grafana: http://localhost:3000

Prometheus reads its scrape configuration from `monitoring/prometheus.yml`; Grafana's Prometheus datasource is provisioned from `monitoring/datasources.yml`.

## Optional centralized logging

Start Loki and Promtail alongside the website and database:

```sh
docker compose -f compose.yaml -f compose.logging.yaml up -d
```

Loki is available at http://localhost:3100. Promtail reads Docker container JSON logs from the host's `/var/lib/docker/containers` directory and forwards them to Loki.

To start both optional stacks at once, include both override files:

```sh
docker compose -f compose.yaml -f compose.monitoring.yaml -f compose.logging.yaml up -d
```

## Other files

- `nginx/default.conf` is a reverse-proxy configuration for an Nginx instance connected to the Compose network.
- `website/admin.php` is a placeholder page; it does not implement authentication or administrative actions.
- `website/assets/css/style.css` remains the stylesheet currently linked by `website/index.php`. The root-level `website/style.css` imports that existing stylesheet.
