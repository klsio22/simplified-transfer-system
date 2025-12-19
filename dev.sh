#!/bin/bash

# Script helper para desenvolvimento
set -e

case "$1" in
  start)
    echo "🚀 Iniciando aplicação..."
    docker-compose up -d --build
    echo "⏳ Aguardando containers ficarem prontos..."
    sleep 30
    docker-compose exec app composer install
    docker-compose exec app php bin/migrate.php
    echo "✅ Aplicação rodando em http://localhost:8080"
    ;;
    
  stop)
    echo "🛑 Parando containers..."
    docker-compose down
    echo "✅ Containers parados"
    ;;
    
  restart)
    echo "🔄 Reiniciando aplicação..."
    docker-compose restart
    echo "✅ Aplicação reiniciada"
    ;;
    
  logs)
    docker-compose logs -f ${2:-app}
    ;;
    
  test)
    echo "🧪 Rodando testes..."
    docker-compose exec app composer test
    ;;
    
  phpstan)
    echo "🔍 Rodando PHPStan..."
    docker-compose exec app composer phpstan
    ;;
    
  cs-fix)
    echo "🎨 Corrigindo formatação..."
    docker-compose exec app composer cs-fixer
    ;;
    
  migrate)
    echo "📊 Rodando migrations..."
    docker-compose exec app php bin/migrate.php
    ;;
    
  worker)
    echo "👷 Iniciando worker..."
    docker-compose exec -d app php bin/worker.php
    echo "✅ Worker iniciado"
    ;;
    
  shell)
    docker-compose exec app bash
    ;;
    
  reset)
    echo "⚠️  ATENÇÃO: Isso vai remover TODOS os dados!"
    read -p "Tem certeza? (y/N) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
      docker-compose down -v
      docker-compose up -d --build
      sleep 30
      docker-compose exec app composer install
      docker-compose exec app php bin/migrate.php
      echo "✅ Reset completo concluído"
    fi
    ;;
    
  health)
    echo "🔍 Verificando saúde da aplicação..."
    echo ""
    echo "📦 Containers:"
    docker-compose ps
    echo ""
    echo "🌐 API:"
    curl -s http://localhost:8080 | jq . || echo "❌ API não está respondendo"
    echo ""
    echo "💾 MySQL:"
    docker-compose exec mysql mysql -uroot -psecret -e "SELECT 1" > /dev/null 2>&1 && echo "✅ MySQL OK" || echo "❌ MySQL com problema"
    echo ""
    echo "🔴 Redis:"
    docker-compose exec redis redis-cli ping > /dev/null 2>&1 && echo "✅ Redis OK" || echo "❌ Redis com problema"
    ;;
    
  *)
    echo "Sistema de Transferências - Helper Script"
    echo ""
    echo "Uso: ./dev.sh [comando]"
    echo ""
    echo "Comandos disponíveis:"
    echo "  start       - Inicia toda a aplicação"
    echo "  stop        - Para os containers"
    echo "  restart     - Reinicia os containers"
    echo "  logs [svc]  - Mostra logs (app, nginx, mysql, redis)"
    echo "  test        - Roda os testes"
    echo "  phpstan     - Roda análise estática"
    echo "  cs-fix      - Corrige formatação do código"
    echo "  migrate     - Roda migrations e seed"
    echo "  worker      - Inicia worker de notificações"
    echo "  shell       - Abre shell no container"
    echo "  reset       - Reset completo (remove dados)"
    echo "  health      - Verifica saúde da aplicação"
    echo ""
    exit 1
    ;;
esac
