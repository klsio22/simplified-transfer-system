.PHONY: help up down build restart logs test phpstan cs-fix shell mysql

# Cores para output
GREEN  := \033[0;32m
YELLOW := \033[0;33m
NC     := \033[0m # No Color

help: ## Mostra esta mensagem de ajuda
	@echo "$(GREEN)Simplified Transfer System - Comandos disponíveis:$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(YELLOW)%-15s$(NC) %s\n", $$1, $$2}'

up: ## Inicia os containers
	@echo "$(GREEN)🚀 Iniciando containers...$(NC)"
	docker-compose up -d
	@echo "$(GREEN)✅ Containers iniciados! Aguarde 30s para o MySQL inicializar.$(NC)"
	@echo "$(YELLOW)📍 API disponível em: http://localhost:8080/transfer$(NC)"

down: ## Para os containers
	@echo "$(YELLOW)🛑 Parando containers...$(NC)"
	docker-compose down
	@echo "$(GREEN)✅ Containers parados!$(NC)"

build: ## Rebuild dos containers
	@echo "$(GREEN)🔨 Fazendo rebuild dos containers...$(NC)"
	docker-compose down -v
	docker-compose build --no-cache
	docker-compose up -d
	@echo "$(GREEN)✅ Rebuild concluído!$(NC)"

restart: ## Reinicia os containers
	@echo "$(YELLOW)🔄 Reiniciando containers...$(NC)"
	docker-compose restart
	@echo "$(GREEN)✅ Containers reiniciados!$(NC)"

logs: ## Mostra logs da aplicação
	docker-compose logs -f app

logs-all: ## Mostra logs de todos os serviços
	docker-compose logs -f

test: ## Executa os testes
	@echo "$(GREEN)🧪 Executando testes...$(NC)"
	docker exec -it transfer-app composer test

test-coverage: ## Executa testes com coverage
	@echo "$(GREEN)📊 Executando testes com cobertura...$(NC)"
	docker exec -it transfer-app composer test:coverage

phpstan: ## Executa análise estática PHPStan
	@echo "$(GREEN)🔍 Executando PHPStan...$(NC)"
	docker exec -it transfer-app composer phpstan

cs-fix: ## Corrige code style
	@echo "$(GREEN)✨ Corrigindo code style...$(NC)"
	docker exec -it transfer-app composer cs-fixer

cs-check: ## Verifica code style
	@echo "$(GREEN)🔍 Verificando code style...$(NC)"
	docker exec -it transfer-app composer cs-check

shell: ## Acessa shell do container da aplicação
	@echo "$(GREEN)🐚 Acessando shell do container...$(NC)"
	docker exec -it transfer-app sh

mysql: ## Acessa MySQL CLI
	@echo "$(GREEN)🐬 Acessando MySQL...$(NC)"
	docker exec -it transfer-mysql mysql -u transfer_user -ptransfer_pass simplified_transfer

redis: ## Acessa Redis CLI
	@echo "$(GREEN)🔴 Acessando Redis...$(NC)"
	docker exec -it transfer-redis redis-cli

install: ## Instala dependências do composer
	@echo "$(GREEN)📦 Instalando dependências...$(NC)"
	docker exec -it transfer-app composer install

transfer: ## Faz uma transferência de teste
	@echo "$(GREEN)💸 Executando transferência de teste...$(NC)"
	curl -X POST http://localhost:8080/transfer \
		-H "Content-Type: application/json" \
		-d '{"value": 50.00, "payer": 1, "payee": 4}' \
		| jq .

check-balance: ## Verifica saldo dos usuários
	@echo "$(GREEN)💰 Saldos atuais:$(NC)"
	docker exec -it transfer-mysql mysql -u transfer_user -ptransfer_pass simplified_transfer \
		-e "SELECT id, fullName, type, balance FROM users;"

clean: ## Remove containers, volumes e cache
	@echo "$(YELLOW)🧹 Limpando tudo...$(NC)"
	docker-compose down -v
	rm -rf coverage/ .phpunit.cache/ .php-cs-fixer.cache
	@echo "$(GREEN)✅ Limpeza concluída!$(NC)"
