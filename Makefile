.PHONY: help start stop restart logs test phpstan cs-fix cs-check migrate worker shell reset health

help: ## Mostra esta ajuda
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

start: ## Inicia a aplicação
	@echo "🚀 Iniciando aplicação..."
	@cp -n .env.example .env 2>/dev/null || true
	@docker-compose up -d --build
	@echo "⏳ Aguardando containers ficarem prontos..."
	@sleep 30
	@docker-compose exec app composer install
	@docker-compose exec app php bin/migrate.php
	@echo "✅ Aplicação rodando em http://localhost:8080"

stop: ## Para os containers
	@echo "🛑 Parando containers..."
	@docker-compose down
	@echo "✅ Containers parados"

restart: ## Reinicia os containers
	@echo "🔄 Reiniciando aplicação..."
	@docker-compose restart
	@echo "✅ Aplicação reiniciada"

logs: ## Mostra logs (use: make logs SVC=app)
	@docker-compose logs -f $(SVC)

test: ## Roda os testes
	@echo "🧪 Rodando testes..."
	@docker-compose exec app composer test

phpstan: ## Roda análise estática
	@echo "🔍 Rodando PHPStan..."
	@docker-compose exec app composer phpstan

cs-fix: ## Corrige formatação do código
	@echo "🎨 Corrigindo formatação..."
	@docker-compose exec app composer cs-fixer

cs-check: ## Verifica formatação do código
	@echo "🔍 Verificando formatação..."
	@docker-compose exec app composer cs-check

migrate: ## Roda migrations e seed
	@echo "📊 Rodando migrations..."
	@docker-compose exec app php bin/migrate.php

worker: ## Inicia worker de notificações
	@echo "👷 Iniciando worker..."
	@docker-compose exec -d app php bin/worker.php
	@echo "✅ Worker iniciado"

shell: ## Abre shell no container
	@docker-compose exec app bash

reset: ## Reset completo (CUIDADO: apaga dados!)
	@echo "⚠️  ATENÇÃO: Isso vai remover TODOS os dados!"
	@read -p "Tem certeza? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		docker-compose down -v; \
		docker-compose up -d --build; \
		sleep 30; \
		docker-compose exec app composer install; \
		docker-compose exec app php bin/migrate.php; \
		echo "✅ Reset completo concluído"; \
	fi

health: ## Verifica saúde da aplicação
	@echo "🔍 Verificando saúde da aplicação..."
	@echo ""
	@echo "📦 Containers:"
	@docker-compose ps
	@echo ""
	@echo "🌐 API:"
	@curl -s http://localhost:8080 | jq . || echo "❌ API não está respondendo"
	@echo ""
	@echo "💾 MySQL:"
	@docker-compose exec mysql mysql -uroot -psecret -e "SELECT 1" > /dev/null 2>&1 && echo "✅ MySQL OK" || echo "❌ MySQL com problema"
	@echo ""
	@echo "🔴 Redis:"
	@docker-compose exec redis redis-cli ping > /dev/null 2>&1 && echo "✅ Redis OK" || echo "❌ Redis com problema"

install: start ## Alias para start
