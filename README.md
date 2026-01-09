# AcervoX

Framework moderno de gestão de acervos digitais para WordPress.

## 📋 Descrição

AcervoX é um plugin WordPress completo para gerenciar acervos digitais, especialmente otimizado para coleções de imagens. Com interface moderna estilo v0.dev, design minimalista e funcionalidades avançadas.

## ✨ Características

- 🎨 Interface moderna e minimalista (estilo v0.dev/shadcn)
- 📚 Gestão de coleções e itens
- 🔍 Filtros avançados e busca
- 📝 Builder de metadados dinâmico
- 🔄 Importação do Tainacan
- 🎯 Shortcode avançado com múltiplos layouts
- 📱 Totalmente responsivo
- ⚡ Performance otimizada

## 🚀 Instalação

### Requisitos

- WordPress 5.0+
- PHP 7.4+
- Node.js 16+ (para desenvolvimento)

### Instalação via Git

```bash
git clone https://github.com/seu-usuario/acervox.git
cd acervox
cd admin
npm install
npm run build
```

### Instalação Manual

1. Faça o download do plugin
2. Extraia na pasta `wp-content/plugins/`
3. Ative o plugin no painel do WordPress
4. Acesse **AcervoX** no menu lateral

## 📦 Estrutura do Projeto

```
acervox/
├── admin/              # Interface React do admin
│   ├── src/           # Código fonte React
│   └── build/         # Arquivos compilados
├── includes/          # Código PHP do plugin
│   ├── api/           # API REST
│   ├── core/          # Core do plugin
│   ├── meta/          # Sistema de metadados
│   ├── post-types/    # Post types customizados
│   └── shortcode/     # Shortcode
├── public/            # Assets públicos (CSS/JS)
└── templates/         # Templates PHP
```

## 🎯 Uso

### Criar uma Coleção

1. Vá em **AcervoX > Coleções**
2. Clique em **Nova Coleção**
3. Configure os metadados em **AcervoX > Metadados**

### Adicionar Itens

1. Vá em **AcervoX > Itens**
2. Adicione novos itens do tipo "Item do Acervo"
3. Atrele à uma coleção usando o botão **Atrelar Coleção**

### Usar o Shortcode

```
[acervox collection="1" per_page="12" layout="grid" columns="3"]
```

**Parâmetros:**
- `id` - ID do shortcode salvo
- `collection` - ID da coleção (opcional)
- `per_page` - Itens por página (padrão: 12)
- `layout` - grid, masonry ou list
- `columns` - Número de colunas (2-6)
- `filters` - true/false (padrão: true)
- `pagination` - true/false (padrão: true)

## 🛠️ Desenvolvimento

### Setup

```bash
# Instalar dependências
cd admin
npm install

# Modo desenvolvimento
npm run dev

# Build para produção
npm run build
```

### Estrutura de Código

- **PHP**: Segue padrões WordPress
- **React**: Componentes funcionais com Hooks
- **CSS**: Design system baseado em variáveis CSS
- **API**: REST API customizada

## 📝 Licença

GPL-2.0-or-later

## 👤 Autor

Mateus Botelho

## 🔗 Links

- [Documentação](#)
- [Issues](https://github.com/seu-usuario/acervox/issues)
- [Changelog](#)
