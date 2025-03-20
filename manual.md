# Manual do Sistema de Controle de Veículos

Este manual descreve todas as funcionalidades e recursos do Sistema de Controle de Veículos, detalhando suas páginas, configurações e orientações de uso.

## Sumário

1. [Introdução](#introdução)
2. [Estrutura do Sistema](#estrutura-do-sistema)
3. [Acesso e Autenticação](#acesso-e-autenticação)
4. [Módulos do Sistema](#módulos-do-sistema)
   - [Dashboard](#dashboard)
   - [Veículos](#veículos)
   - [Uso de Veículos](#uso-de-veículos)
   - [Manutenções](#manutenções)
   - [Abastecimentos](#abastecimentos)
   - [Documentação](#documentação)
   - [Relatórios](#relatórios)
   - [Usuários](#usuários)
5. [Modelos de Dados](#modelos-de-dados)
6. [Recursos Avançados](#recursos-avançados)
7. [Solução de Problemas](#solução-de-problemas)



## BANCO DE DADOS

-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS controle_veiculos;
USE controle_veiculos;

-- Estrutura da tabela `usuarios`
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `login` varchar(50) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel` enum('admin','funcionario') NOT NULL DEFAULT 'funcionario',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inserir usuário administrador padrão
INSERT INTO `usuarios` (`nome`, `login`, `senha`, `nivel`, `ativo`) 
VALUES ('Administrador', 'admin', '123', 'admin', 1);

-- Estrutura da tabela `veiculos`
CREATE TABLE `veiculos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `placa` varchar(10) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `marca` varchar(50) NOT NULL,
  `ano` int(4) NOT NULL,
  `km_atual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('disponivel','em_uso','manutencao') NOT NULL DEFAULT 'disponivel',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `placa` (`placa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estrutura da tabela `uso_veiculos`
CREATE TABLE `uso_veiculos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `veiculo_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_saida` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_retorno` timestamp NULL DEFAULT NULL,
  `km_saida` decimal(10,2) NOT NULL,
  `km_retorno` decimal(10,2) DEFAULT NULL,
  `motivo` varchar(255) NOT NULL,
  `observacoes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `veiculo_id` (`veiculo_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `uso_veiculos_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos` (`id`),
  CONSTRAINT `uso_veiculos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estrutura da tabela `manutencoes`
CREATE TABLE `manutencoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `veiculo_id` int(11) NOT NULL,
  `tipo` varchar(100) NOT NULL,
  `data_inicio` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_fim` timestamp NULL DEFAULT NULL,
  `km_inicio` decimal(10,2) NOT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `finalizada` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `veiculo_id` (`veiculo_id`),
  CONSTRAINT `manutencoes_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estrutura da tabela `abastecimentos`
CREATE TABLE `abastecimentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `veiculo_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo_combustivel` enum('gasolina','etanol','diesel','gnv') NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `km_atual` decimal(10,2) NOT NULL,
  `posto` varchar(100) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `veiculo_id` (`veiculo_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `abastecimentos_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos` (`id`),
  CONSTRAINT `abastecimentos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estrutura da tabela `documentacoes`
CREATE TABLE `documentacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `veiculo_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `status` enum('pendente','pago') NOT NULL DEFAULT 'pendente',
  `observacoes` text DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `veiculo_id` (`veiculo_id`),
  CONSTRAINT `documentacoes_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


## Introdução

O Sistema de Controle de Veículos é uma aplicação web desenvolvida para gerenciar a frota de veículos de uma organização. O sistema controla veículos, usos, manutenções, abastecimentos, documentação e gera relatórios detalhados sobre a operação da frota.

### Principais funcionalidades:

- Cadastro e gerenciamento de veículos
- Controle de uso e quilometragem
- Agendamento e acompanhamento de manutenções
- Registro de abastecimentos e consumo
- Gerenciamento de documentação (IPVA, licenciamento, multas)
- Relatórios e estatísticas
- Controle de acesso por níveis de usuário

## Estrutura do Sistema

O sistema está organizado em uma arquitetura MVC (Model-View-Controller):

- **Models**: Arquivos PHP na pasta `/model` que lidam com acesso ao banco de dados
- **Views**: Arquivos PHP nas pastas principais que contêm a interface de usuário
- **Controllers**: Lógica embutida nos arquivos de view que processam as requisições

### Estrutura de Arquivos:

```
/
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
├── config/
│   └── database.php
├── includes/
│   └── navbar.php
├── model/
│   ├── veiculo.php
│   ├── usuario.php
│   ├── uso_veiculo.php
│   ├── manutencao.php
│   ├── abastecimento.php
│   └── documentacao.php
├── veiculos/
│   └── cadastro.php
├── manutencoes/
│   └── cadastro.php
├── dashboard.php
├── dashboard_funcionario.php
├── index.php
└── perfil.php
```

## Acesso e Autenticação

### Tela de Login

A página inicial (`index.php`) apresenta um formulário de login com:
- Campo para usuário
- Campo para senha
- Botão de acesso

O sistema possui dois níveis de acesso:
1. **Administrador**: Acesso completo a todas as funcionalidades
2. **Funcionário**: Acesso limitado a recursos específicos

### Processo de Autenticação

1. O usuário informa suas credenciais
2. O sistema verifica as credenciais no banco de dados
3. Se válidas, cria uma sessão e redireciona para o dashboard adequado
4. Se inválidas, exibe mensagem de erro

### Perfil de Usuário

A página `perfil.php` permite que qualquer usuário visualize e altere suas informações:
- Visualização dos dados pessoais
- Alteração de senha
- Interface adaptada conforme o nível de acesso (admin ou funcionário)

## Módulos do Sistema

### Dashboard

#### Dashboard Administrativo (`dashboard.php`)

Oferece uma visão geral do sistema com:
- Cards com estatísticas da frota (total de veículos, disponíveis, etc.)
- Resumo de gastos com abastecimentos e manutenções
- Gráficos de abastecimentos por mês
- Gráficos de manutenções por tipo
- Listas dos últimos abastecimentos e manutenções registradas

#### Dashboard do Funcionário (`dashboard_funcionario.php`)

Interface simplificada com acesso a:
- Veículos disponíveis
- Registro de uso
- Perfil do usuário

### Veículos

O módulo de veículos (`veiculos/cadastro.php`) permite:

#### Cadastro de Veículos
- Placa
- Modelo
- Marca
- Ano
- Quilometragem atual

#### Gerenciamento de Status
- Disponível
- Em Uso
- Em Manutenção

#### Ações Disponíveis
- Adicionar novo veículo
- Editar veículo existente
- Excluir veículo (com opção de desativação)
- Iniciar uso de veículo
- Finalizar uso de veículo

### Uso de Veículos

O controle de uso dos veículos é gerenciado através de:

#### Iniciar Uso
- Seleção do veículo
- Seleção do funcionário
- Registro da quilometragem de saída
- Motivo do uso

#### Finalizar Uso
- Registro da quilometragem de retorno
- Observações sobre o uso
- Atualização automática da quilometragem do veículo

### Manutenções

O módulo de manutenções (`manutencoes/cadastro.php`) permite:

#### Registro de Manutenções
- Veículo
- Tipo de manutenção
- Quilometragem no início
- Data de início
- Status (em andamento/finalizada)

#### Finalização de Manutenções
- Data de término
- Valor da manutenção
- Descrição do serviço realizado

#### Acompanhamento
- Visualização de manutenções em andamento
- Histórico de manutenções realizadas
- Exclusão de registros

### Abastecimentos

O módulo de abastecimentos permite:

#### Registro de Abastecimentos
- Veículo
- Data
- Tipo de combustível
- Quantidade
- Valor
- Quilometragem atual
- Posto/Fornecedor

#### Análise de Consumo
- Cálculo automático de média de consumo
- Histórico de abastecimentos por veículo

### Documentação

O módulo de documentação (`documentacao.php`) gerencia:

#### Tipos de Documentos
- IPVA
- Licenciamento
- Multas
- Seguro
- Outros

#### Informações Registradas
- Veículo
- Tipo de documento
- Descrição
- Valor
- Data de vencimento
- Data de pagamento
- Status (pendente/pago)
- Observações

#### Funcionalidades
- Cadastro de documentos
- Edição de registros
- Registro rápido de pagamentos
- Visualização de documentos pendentes
- Alerta de vencimentos próximos
- Exclusão de registros

### Relatórios

O módulo de relatórios (`relatorios/veiculos.php`) apresenta:

#### Estatísticas da Frota
- Veículos por status
- Veículos por marca
- Veículos por ano

#### Gastos
- Top veículos com maiores gastos em abastecimentos
- Top veículos com maiores gastos em manutenções

#### Quilometragem
- Detalhes de quilometragem por veículo
- Contagem de abastecimentos e manutenções

### Usuários

O gerenciamento de usuários permite:

#### Níveis de Acesso
- Administrador: acesso completo ao sistema
- Funcionário: acesso limitado

#### Funcionalidades
- Cadastro de usuários
- Edição de dados
- Alteração de senha
- Ativação/desativação de contas

## Modelos de Dados

### Veículo (`model/veiculo.php`)

Gerencia os dados dos veículos com métodos para:
- Criar, ler, atualizar e excluir veículos
- Listar veículos (todos, ativos, disponíveis)
- Contar veículos (total, em manutenção)
- Controlar status (disponível, em uso, em manutenção)
- Atualizar quilometragem
- Obter estatísticas

### Uso de Veículo (`model/uso_veiculo.php`)

Controla o uso dos veículos com métodos para:
- Iniciar e finalizar uso
- Listar histórico de uso
- Obter informações de uso atual

### Manutenção (`model/manutencao.php`)

Gerencia manutenções com métodos para:
- Registrar nova manutenção
- Finalizar manutenção
- Listar manutenções (em andamento, finalizadas)
- Calcular custos totais

### Abastecimento (`model/abastecimento.php`)

Controla abastecimentos com métodos para:
- Registrar abastecimentos
- Listar histórico
- Calcular médias de consumo
- Somar gastos

### Documentação (`model/documentacao.php`)

Gerencia documentos com métodos para:
- Registrar documentos
- Atualizar status
- Listar por tipo, status ou veículo
- Identificar vencimentos próximos
- Calcular totais pagos e pendentes

### Usuário (`model/usuario.php`)

Gerencia dados dos usuários com métodos para:
- Autenticar usuários
- Criar, ler, atualizar e excluir usuários
- Alterar senhas
- Listar usuários ativos

## Recursos Avançados

### Gerenciamento de Veículos Inativos

O sistema permite desativar veículos em vez de excluí-los, preservando o histórico:
- Opção de desativação no lugar de exclusão
- Filtro para mostrar/ocultar veículos inativos
- Possibilidade de reativar veículos

### Exclusão Completa

Para casos especiais, é possível forçar a exclusão completa de um veículo:
- Remove o veículo e todos os registros associados
- Utiliza transações de banco de dados para garantir integridade

### Alertas de Vencimento

O sistema monitora documentos prestes a vencer:
- Destaque para documentos vencendo nos próximos 30 dias
- Indicadores visuais para documentos vencidos

### Interface Adaptativa

A interface se adapta ao nível do usuário:
- Menu completo para administradores
- Interface simplificada para funcionários
- Acesso a perfil para todos os níveis

## Solução de Problemas

### Problemas Comuns e Soluções

#### Erro ao Excluir Veículo
- **Problema**: Não é possível excluir um veículo com registros associados
- **Solução**: Use a função de desativação ou force a exclusão com a opção "Forçar Exclusão"

#### Erro 500 ao Acessar Páginas
- **Problema**: Erro interno do servidor ao acessar determinadas páginas
- **Solução**: Verifique os métodos necessários nos arquivos de modelo

#### Inconsistência de Quilometragem
- **Problema**: Erro ao registrar quilometragem menor que a anterior
- **Solução**: O sistema impede registros com quilometragem inferior à atual, verifique o valor inserido

#### Acesso Negado
- **Problema**: Usuário não consegue acessar determinada página
- **Solução**: Verifique o nível de acesso do usuário (admin/funcionário)

### Manutenção do Sistema

#### Backup do Banco de Dados
Recomenda-se fazer backup periódico do banco de dados para evitar perda de dados.

#### Atualização
Para atualizações futuras, verifique a compatibilidade com os módulos existentes antes de implementá-las.

#### Segurança
Mantenha as senhas seguras e altere-as periodicamente, especialmente para contas administrativas.
