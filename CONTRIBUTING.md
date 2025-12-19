# Guia de Contribuição

## Como contribuir

Obrigado por considerar contribuir com o projeto! Este guia irá ajudá-lo a começar.

## Requisitos

- Docker e Docker Compose
- Git
- Conhecimento em PHP 8.2+
- Conhecimento em Slim Framework

## Setup do ambiente

```bash
# 1. Fork o projeto
# 2. Clone seu fork
git clone https://github.com/seu-usuario/simplified-transfer-system.git
cd simplified-transfer-system

# 3. Configure o ambiente
cp .env.example .env
./dev.sh start

# 4. Crie uma branch para sua feature
git checkout -b feature/minha-feature
```

## Workflow

1. **Crie uma issue** descrevendo o problema ou feature
2. **Desenvolva** em uma branch separada
3. **Escreva testes** para sua mudança
4. **Rode os testes** e análise estática
5. **Commit** com mensagens claras
6. **Push** para seu fork
7. **Abra um Pull Request**

## Padrões de código

### PSR-12

Todo código deve seguir PSR-12:

```bash
# Antes de commitar
./dev.sh cs-fix
```

### PHPStan

Código deve passar no PHPStan nível 8:

```bash
./dev.sh phpstan
```

### Testes

Mantenha cobertura > 70%:

```bash
./dev.sh test
```

## Convenções

### Commits

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: adiciona autenticação JWT
fix: corrige validação de CPF
docs: atualiza README com exemplos
test: adiciona testes para WalletService
refactor: extrai validação para classe separada
```

### Nomenclatura

- **Classes**: PascalCase (`UserRepository`)
- **Métodos**: camelCase (`findById`)
- **Variáveis**: camelCase (`$userId`)
- **Constantes**: UPPER_SNAKE_CASE (`MAX_TRANSFER_AMOUNT`)
- **Arquivos**: PascalCase para classes, kebab-case para configs

### Estrutura de código

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Repositories\UserRepository;

/**
 * Serviço de transferências
 */
class TransferService
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    /**
     * Executa uma transferência
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public function transfer(array $data): array
    {
        // Implementação
    }
}
```

## Tipos de contribuição

### 🐛 Reportar bugs

Abra uma issue com:
- Descrição clara do problema
- Steps to reproduce
- Comportamento esperado vs atual
- Versão do PHP, Docker, etc

### ✨ Sugerir features

Abra uma issue com:
- Descrição da feature
- Caso de uso / problema que resolve
- Proposta de implementação (opcional)

### 📝 Melhorar documentação

- Corrija typos
- Adicione exemplos
- Melhore explicações
- Traduza para outros idiomas

### 🧪 Adicionar testes

- Aumente cobertura de testes
- Adicione testes de edge cases
- Crie testes de carga

### 🔧 Refatorar código

- Melhore legibilidade
- Otimize performance
- Reduza complexidade
- Aplique design patterns

## Checklist do Pull Request

Antes de abrir um PR, verifique:

- [ ] Código segue PSR-12
- [ ] Passou no PHPStan nível 8
- [ ] Testes passam
- [ ] Cobertura não diminuiu
- [ ] Documentação atualizada
- [ ] CHANGELOG atualizado (se aplicável)
- [ ] Commits seguem Conventional Commits

## Revisão de código

PRs serão revisados considerando:

1. **Qualidade**: Código limpo e bem estruturado
2. **Testes**: Cobertura adequada
3. **Performance**: Sem impacto negativo
4. **Segurança**: Sem vulnerabilidades
5. **Documentação**: Bem documentado

## Perguntas?

- Abra uma issue com a tag `question`
- Entre em contato via email
- Consulte a [documentação](docs/)

## Código de Conduta

Seja respeitoso e construtivo. Queremos uma comunidade acolhedora para todos.
