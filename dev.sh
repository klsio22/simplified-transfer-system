#!/bin/bash

# dev.sh - Script de desenvolvimento para Simplified Transfer System
# Uso: ./dev.sh [comando]

set -e

# Cores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Função helper
print_help() {
    echo -e "${GREEN}Simplified Transfer System - Script de Desenvolvimento${NC}"
    echo ""
    echo "Uso: ./dev.sh [comando]"
    echo ""
    echo "Comandos disponíveis:"
    echo "  start       - Inicia os containers Docker"
    echo "  stop        - Para os containers"
    echo "  restart     - Reinicia os containers"
    echo "  build       - Rebuild completo dos containers"
    echo "  logs        - Mostra logs da aplicação"
    echo "  test        - Executa os testes"
    echo "  phpstan     - Executa análise estática"
    echo "  cs-fix      - Corrige code style"
    echo "  shell       - Acessa shell do container"
    echo "  mysql       - Acessa MySQL CLI"
    echo "  transfer    - Faz transferência de teste"
    echo "  balance     - Verifica saldos dos usuários"
    echo "  clean       - Remove containers e volumes"
    echo "  help        - Mostra esta mensagem"
}

# Verifica se Docker está instalado
check_docker() {
    if ! command -v docker &> /dev/null; then
        echo -e "${RED}❌ Docker não encontrado. Instale o Docker primeiro.${NC}"
        exit 1
    fi

    if ! command -v docker-compose &> /dev/null; then
        echo -e "${RED}❌ Docker Compose não encontrado. Instale o Docker Compose primeiro.${NC}"
        exit 1
    fi
}

# Comandos
cmd_start() {
    echo -e "${GREEN}🚀 Iniciando containers...${NC}"
    docker-compose up -d
    echo -e "${GREEN}✅ Containers iniciados!${NC}"
    echo -e "${YELLOW}📍 Aguarde 30 segundos para o MySQL inicializar${NC}"
    echo -e "${YELLOW}📍 API disponível em: http://localhost:8080/transfer${NC}"
}

cmd_stop() {
    echo -e "${YELLOW}🛑 Parando containers...${NC}"
    docker-compose down
    echo -e "${GREEN}✅ Containers parados!${NC}"
}

cmd_restart() {
    echo -e "${YELLOW}🔄 Reiniciando containers...${NC}"
    docker-compose restart
    echo -e "${GREEN}✅ Containers reiniciados!${NC}"
}

cmd_build() {
    echo -e "${GREEN}🔨 Fazendo rebuild dos containers...${NC}"
    docker-compose down -v
    docker-compose build --no-cache
    docker-compose up -d
    echo -e "${GREEN}✅ Rebuild concluído!${NC}"
}

cmd_logs() {
    echo -e "${GREEN}📋 Mostrando logs...${NC}"
    docker-compose logs -f app
}

cmd_test() {
    echo -e "${GREEN}🧪 Executando testes...${NC}"
    docker exec -it transfer-app composer test
}

cmd_phpstan() {
    echo -e "${GREEN}🔍 Executando PHPStan...${NC}"
    docker exec -it transfer-app composer phpstan
}

cmd_cs_fix() {
    echo -e "${GREEN}✨ Corrigindo code style...${NC}"
    docker exec -it transfer-app composer cs-fixer
}

cmd_shell() {
    echo -e "${GREEN}🐚 Acessando shell do container...${NC}"
    docker exec -it transfer-app sh
}

cmd_mysql() {
    echo -e "${GREEN}🐬 Acessando MySQL...${NC}"
    docker exec -it transfer-mysql mysql -u transfer_user -ptransfer_pass simplified_transfer
}

cmd_transfer() {
    echo -e "${GREEN}💸 Executando transferência de teste...${NC}"
    curl -X POST http://localhost:8080/transfer \
        -H "Content-Type: application/json" \
        -d '{"value": 50.00, "payer": 1, "payee": 4}' \
        -s | jq . || echo '{"error": "jq não instalado - instale com: sudo apt install jq"}'
}

cmd_balance() {
    echo -e "${GREEN}💰 Verificando saldos...${NC}"
    docker exec -it transfer-mysql mysql -u transfer_user -ptransfer_pass simplified_transfer \
        -e "SELECT id, full_name, type, balance FROM users;"
}

cmd_clean() {
    echo -e "${YELLOW}🧹 Limpando tudo...${NC}"
    docker-compose down -v
    rm -rf coverage/ .phpunit.cache/ .php-cs-fixer.cache
    echo -e "${GREEN}✅ Limpeza concluída!${NC}"
}

# Main
check_docker

case "${1:-help}" in
    start)
        cmd_start
        ;;
    stop)
        cmd_stop
        ;;
    restart)
        cmd_restart
        ;;
    build)
        cmd_build
        ;;
    logs)
        cmd_logs
        ;;
    test)
        cmd_test
        ;;
    phpstan)
        cmd_phpstan
        ;;
    cs-fix)
        cmd_cs_fix
        ;;
    shell)
        cmd_shell
        ;;
    mysql)
        cmd_mysql
        ;;
    transfer)
        cmd_transfer
        ;;
    balance)
        cmd_balance
        ;;
    clean)
        cmd_clean
        ;;
    help|*)
        print_help
        ;;
esac
