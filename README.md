# AcervoX

<div align="center">

![Version](https://img.shields.io/badge/version-0.3.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green.svg)

**Framework profissional e moderno para gestão de acervos digitais no WordPress**

[Características](#-características) • [Instalação](#-instalação) • [Documentação](#-documentação) • [Desenvolvimento](#-desenvolvimento)

</div>

---

## 📖 Sobre

AcervoX é uma solução completa e profissional para gerenciamento de acervos digitais no WordPress. Desenvolvido com tecnologias modernas (React, REST API, PHP 7.4+), oferece uma experiência de usuário excepcional tanto para administradores quanto para visitantes do site.

Ideal para museus, galerias, bibliotecas digitais, arquivos históricos e qualquer instituição que precise organizar, catalogar e exibir coleções digitais de forma elegante e eficiente.

## ✨ Características

### 🎨 Interface Administrativa Moderna
- **Dashboard React** com design moderno e elegante
- Interface intuitiva e responsiva
- Feedback visual em tempo real
- Sistema de notificações (Toast)

### 📚 Gestão de Coleções e Itens
- Criação e organização de coleções ilimitadas
- Gestão completa de itens com metadados personalizados
- Vinculação flexível de itens a coleções
- Exclusão em cascata (coleção + itens vinculados)

### 🔧 Sistema de Metadados Dinâmico
- **Builder de Metadados** visual e intuitivo
- Criação automática de campos a partir de importações CSV
- Suporte a múltiplos tipos: texto, textarea, número, select
- Metadados específicos por coleção
- Mapeamento inteligente de campos

### 📥 Importação de Dados
- **Importação CSV** com mapeamento automático
- Detecção automática de colunas e tipos
- Importação de imagens (URLs externas e locais)
- Processamento em lotes para grandes volumes
- Barra de progresso em tempo real
- Histórico completo de importações
- **Importação de Sistemas Externos** (integração nativa)

### 🎯 Exibição Pública
- **Shortcodes avançados** com múltiplos layouts
- Layouts: Grid, Masonry e Lista
- Filtros e busca avançada
- Paginação configurável
- Totalmente responsivo
- Performance otimizada

### 🔍 Funcionalidades Avançadas
- **API REST completa** e documentada
- **Busca avançada** full-text em todos os metadados
- **Filtros múltiplos** combinados (AND/OR) por metadados, tags e categorias
- **Filtros por range de datas** e valores numéricos
- **Taxonomias personalizadas** (Tags e Categorias hierárquicas)
- **Galeria de mídia avançada** com múltiplas imagens, vídeos e documentos por item
- **Exportação de dados** em múltiplos formatos (CSV, JSON, XML)
- **Edição em massa** de itens
- **Duplicação de itens** com todos os metadados e mídias
- Featured images automáticas
- URLs amigáveis (SEO-friendly)

## 🚀 Instalação

### Requisitos do Sistema

- **WordPress**: 5.0 ou superior
- **PHP**: 7.4 ou superior
- **MySQL**: 5.6 ou superior
- **Node.js**: 16+ (apenas para desenvolvimento)

### Instalação via Git (Desenvolvimento)

```bash
# Clonar repositório
git clone https://github.com/botelllhx/acervo-x.git
cd acervox

# Instalar dependências do frontend
cd admin
npm install

# Compilar assets
npm run build

# Voltar para raiz
cd ..
```

### Instalação Manual

1. Faça o download do plugin
2. Extraia o arquivo na pasta `wp-content/plugins/`
3. Ative o plugin no painel administrativo do WordPress
4. Acesse **AcervoX** no menu lateral do admin

### Pós-Instalação

Após a ativação, o plugin criará automaticamente:
- Post types customizados (Coleções e Itens)
- Taxonomias personalizadas
- Tabelas de histórico (se necessário)
- Estrutura de diretórios para uploads

## 📖 Documentação

### Criando sua Primeira Coleção

1. Acesse **AcervoX > Coleções** no menu do WordPress
2. Clique em **Nova Coleção**
3. Preencha o título e descrição da coleção
4. Salve a coleção

### Configurando Metadados

1. Acesse **AcervoX > Metadados**
2. Selecione a coleção desejada
3. Clique em **Adicionar Campo**
4. Configure:
   - **Label**: Nome do campo (ex: "Autor", "Data", "Material")
   - **Key**: Chave única (gerada automaticamente)
   - **Tipo**: Texto, Textarea, Número ou Select
5. Salve os campos

### Adicionando Itens

#### Via Interface WordPress

1. Acesse **AcervoX > Itens**
2. Clique em **Adicionar Novo Item**
3. Preencha título, descrição e conteúdo
4. Adicione uma imagem destacada (featured image)
5. Selecione a coleção no metabox
6. Preencha os metadados personalizados
7. Publique o item

#### Via Importação CSV

1. Acesse **AcervoX > Importar CSV**
2. Selecione a coleção de destino
3. Faça upload do arquivo CSV
4. Clique em **Processar CSV**
5. Revise os dados processados
6. Clique em **Iniciar Importação**

**Formato CSV esperado:**
- Primeira linha: cabeçalhos das colunas
- Coluna de título obrigatória (title, titulo, nome)
- Coluna de imagem opcional (special_thumbnail, thumbnail, imagem, image)
- Metadados serão mapeados automaticamente

**Exemplo de CSV:**
```csv
Título,Descrição,Material,Data,special_thumbnail
"Obra de Arte","Descrição detalhada","Óleo sobre tela","2020","https://exemplo.com/imagem.jpg"
```

### Usando Shortcodes

#### Shortcode Básico

```php
[acervox collection="1"]
```

#### Shortcode Completo

```php
[acervox 
    collection="1" 
    per_page="12" 
    layout="grid" 
    columns="3" 
    filters="true" 
    pagination="true"
]
```

#### Parâmetros Disponíveis

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `id` | integer | - | ID do shortcode salvo (sobrescreve outros parâmetros) |
| `collection` | integer | - | ID da coleção a exibir |
| `per_page` | integer | 12 | Número de itens por página |
| `layout` | string | grid | Layout: `grid`, `masonry` ou `list` |
| `columns` | integer | 3 | Número de colunas (2-6, apenas para grid) |
| `filters` | boolean | true | Exibir filtros de busca |
| `pagination` | boolean | true | Exibir paginação |

#### Exemplos de Uso

**Grid com 4 colunas:**
```php
[acervox collection="1" layout="grid" columns="4"]
```

**Masonry sem filtros:**
```php
[acervox collection="2" layout="masonry" filters="false"]
```

**Lista com paginação:**
```php
[acervox collection="3" layout="list" per_page="20"]
```

### API REST

O AcervoX expõe uma API REST completa para integração com outros sistemas.

#### Endpoints Principais

**Listar Coleções:**
```
GET /wp-json/acervox/v1/collections
```

**Listar Itens:**
```
GET /wp-json/acervox/v1/items?collection=1&per_page=12&page=1
```

**Buscar Itens:**
```
GET /wp-json/acervox/v1/items?search=termo&collection=1
```

**Filtrar por Metadado:**
```
GET /wp-json/acervox/v1/items?meta_key=autor&meta_value=Van Gogh
```

**Busca Avançada com Filtros Múltiplos:**
```
GET /wp-json/acervox/v1/items?search=termo&tags[]=1&tags[]=2&date_from=2020-01-01&date_to=2023-12-31
```

**Exportar Dados:**
```
POST /wp-json/acervox/v1/export/csv
POST /wp-json/acervox/v1/export/json
POST /wp-json/acervox/v1/export/xml
Body: { "collection_id": 1, "item_ids": [] }
```

**Gerenciar Galeria:**
```
GET /wp-json/acervox/v1/items/{id}/gallery
POST /wp-json/acervox/v1/items/{id}/gallery
DELETE /wp-json/acervox/v1/items/{id}/gallery/{attachment_id}
POST /wp-json/acervox/v1/items/{id}/gallery/order
```

**Tags e Categorias:**
```
GET /wp-json/acervox/v1/tags
GET /wp-json/acervox/v1/categories
POST /wp-json/acervox/v1/items/{id}/tags
POST /wp-json/acervox/v1/items/{id}/categories
```

**Edição em Massa e Duplicação:**
```
POST /wp-json/acervox/v1/items/bulk-update
POST /wp-json/acervox/v1/items/{id}/duplicate
```

#### Resposta de Exemplo

```json
{
  "items": [
    {
      "id": 123,
      "title": "Obra de Arte",
      "excerpt": "Descrição curta...",
      "content": "Conteúdo completo...",
      "permalink": "https://site.com/acervo/obra-de-arte",
      "thumbnails": {
        "thumbnail": "https://...",
        "medium": "https://...",
        "large": "https://...",
        "full": "https://..."
      },
      "collection_id": 1,
      "gallery": [
        {
          "id": 456,
          "type": "image",
          "url": "https://...",
          "thumbnails": {
            "thumbnail": "https://...",
            "medium": "https://...",
            "large": "https://...",
            "full": "https://..."
          },
          "order": 0
        }
      ],
      "taxonomies": {
        "acervox_tag": [
          {
            "id": 1,
            "name": "Arte Moderna",
            "slug": "arte-moderna"
          }
        ],
        "acervox_category": [
          {
            "id": 2,
            "name": "Pinturas",
            "slug": "pinturas"
          }
        ]
      },
      "meta": {
        "autor": {
          "label": "Autor",
          "type": "text",
          "value": "Van Gogh"
        }
      }
    }
  ],
  "total": 50,
  "pages": 5,
  "current_page": 1
}
```

## 🏗️ Estrutura do Projeto

```
acervox/
├── admin/                      # Interface React do administrador
│   ├── src/
│   │   ├── components/        # Componentes React
│   │   │   ├── Dashboard.jsx
│   │   │   ├── Collections.jsx
│   │   │   ├── Items.jsx
│   │   │   ├── ImportCSV.jsx
│   │   │   ├── ImportExternal.jsx
│   │   │   ├── ImportHistory.jsx
│   │   │   ├── MetadataBuilder.jsx
│   │   │   ├── ExportData.jsx
│   │   │   └── ui/            # Componentes UI reutilizáveis
│   │   ├── lib/               # Utilitários
│   │   ├── main.jsx           # Entry point
│   │   └── styles.css         # Estilos globais
│   ├── build/                 # Assets compilados
│   ├── package.json
│   └── vite.config.js
├── includes/                  # Código PHP do plugin
│   ├── api/                   # API REST
│   │   ├── items.php
│   │   ├── rest.php
│   │   └── export.php
│   ├── core/                  # Core do plugin
│   │   ├── activator.php
│   │   ├── deactivator.php
│   │   └── loader.php
│   ├── importer/              # Sistema de importação
│   │   ├── csv.php
│   │   ├── external.php
│   │   ├── external-mapper.php
│   │   ├── history.php
│   │   └── logger.php
│   ├── meta/                  # Sistema de metadados
│   │   ├── fields.php
│   │   ├── metaboxes.php
│   │   ├── registry.php
│   │   └── gallery.php
│   ├── post-types/            # Post types customizados
│   │   ├── collection.php
│   │   └── item.php
│   ├── shortcode/             # Shortcode
│   │   └── acervo.php
│   └── taxonomies/            # Taxonomias
│       └── generic.php
├── public/                    # Assets públicos
│   ├── assets/
│   │   ├── css/
│   │   └── js/
│   └── public.php
├── templates/                 # Templates PHP
│   ├── grid.php
│   ├── list.php
│   └── single-acervox_item.php
├── acervox.php                # Arquivo principal
├── composer.json
└── README.md
```

## 🛠️ Desenvolvimento

### Setup do Ambiente

```bash
# 1. Clonar repositório
git clone https://github.com/botelllhx/acervox.git
cd acervox

# 2. Instalar dependências PHP (se usar Composer)
composer install

# 3. Instalar dependências Node.js
cd admin
npm install

# 4. Modo desenvolvimento (watch mode)
npm run dev

# 5. Build para produção
npm run build
```

### Scripts Disponíveis

```bash
# Desenvolvimento (watch mode)
npm run dev

# Build de produção
npm run build

# Verificar lint
npm run lint
```

### Padrões de Código

- **PHP**: Segue os [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- **JavaScript/React**: ESLint configurado
- **CSS**: Design system baseado em variáveis CSS (inspirado em shadcn/ui)

### Estrutura de Desenvolvimento

- **Componentes React**: Funcionais com Hooks
- **API REST**: Endpoints customizados em `/wp-json/acervox/v1/`
- **Hooks WordPress**: Actions e filters para extensibilidade
- **Namespaces**: Classes organizadas por funcionalidade

## 🔄 Importação de Dados

### Importação CSV

O AcervoX suporta importação em massa via CSV com recursos avançados:

- ✅ **Mapeamento automático** de colunas para metadados
- ✅ **Criação automática** de campos de metadados
- ✅ **Importação de imagens** de URLs externas
- ✅ **Processamento em lotes** para grandes volumes
- ✅ **Barra de progresso** em tempo real
- ✅ **Logs detalhados** de importação
- ✅ **Histórico completo** de importações

### Importação de Sistemas Externos

Integração nativa com sistemas externos de gestão de acervos:

1. Acesse **AcervoX > Importar > Sistema Externo**
2. Selecione a coleção do sistema externo
3. Configure o mapeamento de metadados
4. Inicie a importação

O AcervoX detecta automaticamente se o sistema externo está ativo e disponibiliza suas coleções para importação.

## 🎨 Personalização

### Templates

Os templates podem ser sobrescritos no tema ativo:

```
seu-tema/
└── acervox/
    ├── grid.php
    ├── list.php
    └── single-acervox_item.php
```

### Hooks e Filtros

O plugin expõe vários hooks para personalização:

```php
// Filtrar dados do item antes de exibir
add_filter('acervox_item_data', function($data, $post_id) {
    // Modificar $data
    return $data;
}, 10, 2);

// Modificar query de itens
add_action('acervox_before_query', function($query_args) {
    // Modificar $query_args
});
```

## 📊 Performance

- **Lazy loading** de imagens
- **Cache** de queries
- **Otimização** de assets (minificação)
- **Processamento assíncrono** de importações
- **Paginação eficiente**

## 🔒 Segurança

- Validação e sanitização de todos os inputs
- Nonces para todas as requisições AJAX
- Permissões e capabilities do WordPress
- Sanitização de dados de saída
- Proteção contra SQL injection

## 🤝 Contribuindo

Contribuições são bem-vindas! Por favor:

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

### Guidelines

- Siga os padrões de código do projeto
- Adicione testes quando possível
- Documente mudanças significativas
- Mantenha commits atômicos e mensagens claras

## 📝 Changelog

### 0.3.0 (Atual)
- ✨ **Exportação de Dados**: Suporte completo a exportação em CSV, JSON e XML
- ✨ **Galeria de Mídia Avançada**: Sistema completo para múltiplas imagens, vídeos e documentos por item
- ✨ **Sistema de Tags e Categorias**: API REST completa para gerenciamento de tags e categorias hierárquicas
- ✨ **Busca Avançada**: Busca full-text em todos os metadados com filtros múltiplos combinados (AND/OR)
- ✨ **Filtros Avançados**: Filtros por range de datas, valores numéricos, tags e categorias
- ✨ **Edição em Massa**: Atualização em lote de múltiplos itens simultaneamente
- ✨ **Duplicação de Itens**: Duplicar itens completos incluindo metadados, galeria e taxonomias
- 🔧 Melhorias na API REST com novos endpoints para galeria e taxonomias
- 🔧 Integração de galeria na resposta padrão de itens

### 0.2.0
- ✨ Importação CSV com mapeamento automático de metadados
- ✨ Importação automática de imagens de URLs externas
- ✨ Criação automática de campos de metadados a partir do CSV
- ✨ Barra de progresso em tempo real na importação
- ✨ Histórico completo de importações
- ✨ Exclusão em cascata de coleções e itens
- 🐛 Correções na barra de progresso
- 🐛 Melhorias no mapeamento de metadados

### 0.1.0
- 🎉 Lançamento inicial
- ✨ Interface administrativa React
- ✨ Sistema de coleções e itens
- ✨ Builder de metadados
- ✨ Shortcodes avançados
- ✨ API REST completa

## 📄 Licença

Este projeto está licenciado sob a **GPL-2.0-or-later**.

```
Copyright (C) 2024 Mateus Botelho

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

## 👤 Autor

**Mateus Botelho**

- GitHub: [@botelllhx](https://github.com/botelllhx)
- Email: mateusbotelho9779@gmail.com

## 🙏 Agradecimentos

- WordPress Community
- React Team

## 🔗 Links Úteis

- [Documentação Completa](#) (em breve)
- [Reportar Bug](https://github.com/botelllhx/acervox/issues)
- [Solicitar Feature](https://github.com/botelllhx/acervox/issues)
- [Changelog Completo](#)

---

<div align="center">

**Desenvolvido com ❤️ para a comunidade WordPress**

⭐ Se este projeto foi útil, considere dar uma estrela!

</div>
