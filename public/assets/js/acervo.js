/**
 * AcervoX Frontend - JavaScript Avançado
 * Shortcode com design lindo e modal elegante
 */

(function() {
  'use strict';

  // Verificar se AcervoXFront está disponível
  if (typeof AcervoXFront === 'undefined') {
    console.error('AcervoX: AcervoXFront não está definido. Verifique se o script está sendo carregado corretamente.');
    return;
  }

  document.addEventListener('DOMContentLoaded', function() {
    const containers = document.querySelectorAll('.acervox-shortcode');
    if (containers.length === 0) {
      console.warn('AcervoX: Nenhum container .acervox-shortcode encontrado.');
      return;
    }
    containers.forEach(container => {
      try {
        new AcervoXFrontend(container);
      } catch (error) {
        console.error('AcervoX: Erro ao inicializar container:', error, container);
      }
    });
  });

  class AcervoXFrontend {
    constructor(container) {
      this.container = container;
      
      // Parse seguro do data-config
      let config = {};
      try {
        const configAttr = container.getAttribute('data-config');
        if (configAttr) {
          config = JSON.parse(configAttr);
        }
      } catch (error) {
        console.error('AcervoX: Erro ao parsear data-config:', error);
        config = {};
      }
      
      this.config = config;
      this.currentPage = 1;
      this.items = [];
      this.loading = false;
      this.collections = [];
      this.taxonomies = [];
      this.filters = {
        search: '',
        collection: this.config.collection || '',
        taxonomy: '',
        term: '',
        orderby: 'date',
        order: 'DESC'
      };

      this.init();
    }

    async init() {
      try {
        await this.loadCollections();
        this.renderFilters();
        this.loadItems();
        this.setupEventListeners();
      } catch (error) {
        console.error('AcervoX: Erro na inicialização:', error);
        this.showError();
      }
    }

    async loadCollections() {
      try {
        if (!AcervoXFront || !AcervoXFront.api) {
          console.error('AcervoX: AcervoXFront.api não está disponível');
          return;
        }
        
        const response = await fetch(`${AcervoXFront.api}/collections`);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        if (data && data.collections) {
          this.collections = data.collections;
        }
      } catch (error) {
        console.error('AcervoX: Erro ao carregar coleções:', error);
        // Continuar mesmo sem coleções
        this.collections = [];
      }
    }

    renderFilters() {
      if (!this.config.filters) return;

      const hasCollection = this.config.collection;
      
      let filtersHTML = '<div class="acervox-filters">';

      // Se não tem coleção definida, mostrar select de coleções
      if (!hasCollection) {
        filtersHTML += `
          <div class="acervox-filter-group">
            <label class="acervox-filter-label">Coleção</label>
            <select class="acervox-filter-collection acervox-select">
              <option value="">Todas as coleções</option>
              ${this.collections.map(col => 
                `<option value="${col.id}" ${this.filters.collection == col.id ? 'selected' : ''}>${col.title}</option>`
              ).join('')}
            </select>
          </div>
        `;
      }

      // Busca avançada (sempre visível)
      filtersHTML += `
        <div class="acervox-filter-group">
          <label class="acervox-filter-label">Buscar</label>
          <div class="acervox-filter-search-wrapper">
            <input 
              type="text" 
              placeholder="Buscar itens..." 
              class="acervox-filter-search acervox-input"
              value="${this.filters.search}"
            />
          </div>
        </div>
      `;

      // Se tem coleção definida, mostrar filtros avançados
      if (hasCollection) {
        filtersHTML += `
          <div class="acervox-filter-group">
            <label class="acervox-filter-label">Ordenar por</label>
            <select class="acervox-filter-orderby acervox-select">
              <option value="date" ${this.filters.orderby === 'date' ? 'selected' : ''}>Data</option>
              <option value="title" ${this.filters.orderby === 'title' ? 'selected' : ''}>Título</option>
              <option value="modified" ${this.filters.orderby === 'modified' ? 'selected' : ''}>Modificado</option>
            </select>
          </div>
          <div class="acervox-filter-group">
            <label class="acervox-filter-label">Visualização</label>
            <select class="acervox-filter-layout acervox-select">
              <option value="grid" ${this.config.layout === 'grid' ? 'selected' : ''}>Grid</option>
              <option value="masonry" ${this.config.layout === 'masonry' ? 'selected' : ''}>Masonry</option>
              <option value="list" ${this.config.layout === 'list' ? 'selected' : ''}>Lista</option>
            </select>
          </div>
        `;
      }

      filtersHTML += `
        <div class="acervox-filter-group" style="display: flex; align-items: flex-end;">
          <button class="acervox-filter-clear acervox-btn acervox-btn-outline" type="button">
            Limpar
          </button>
        </div>
      </div>`;

      this.container.insertAdjacentHTML('afterbegin', filtersHTML);
    }

    setupEventListeners() {
      // Busca
      const searchInput = this.container.querySelector('.acervox-filter-search');
      if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
          clearTimeout(searchTimeout);
          searchTimeout = setTimeout(() => {
            this.filters.search = e.target.value;
            this.currentPage = 1;
            this.loadItems();
          }, 500);
        });
      }

      // Filtro de coleção
      const collectionSelect = this.container.querySelector('.acervox-filter-collection');
      if (collectionSelect) {
        collectionSelect.addEventListener('change', (e) => {
          this.filters.collection = e.target.value;
          this.currentPage = 1;
          this.loadItems();
        });
      }

      // Ordenar por
      const orderbySelect = this.container.querySelector('.acervox-filter-orderby');
      if (orderbySelect) {
        orderbySelect.addEventListener('change', (e) => {
          this.filters.orderby = e.target.value;
          this.currentPage = 1;
          this.loadItems();
        });
      }

      // Layout
      const layoutSelect = this.container.querySelector('.acervox-filter-layout');
      if (layoutSelect) {
        layoutSelect.addEventListener('change', (e) => {
          this.config.layout = e.target.value;
          this.renderItems();
        });
      }

      // Limpar filtros
      const clearBtn = this.container.querySelector('.acervox-filter-clear');
      if (clearBtn) {
        clearBtn.addEventListener('click', () => {
          this.filters = { search: '', collection: this.config.collection || '', taxonomy: '', term: '', orderby: 'date', order: 'DESC' };
          if (searchInput) searchInput.value = '';
          if (collectionSelect) collectionSelect.value = '';
          if (orderbySelect) orderbySelect.value = 'date';
          this.currentPage = 1;
          this.loadItems();
        });
      }
    }

    async loadItems() {
      if (this.loading) return;
      
      if (!AcervoXFront || !AcervoXFront.api) {
        console.error('AcervoX: AcervoXFront.api não está disponível');
        this.showError();
        return;
      }
      
      this.loading = true;
      this.showLoading();

      try {
        const params = new URLSearchParams({
          page: this.currentPage,
          per_page: this.config.per_page || 12,
          orderby: this.filters.orderby || 'date',
          order: this.filters.order || 'DESC',
        });

        if (this.filters.collection || this.config.collection) {
          params.append('collection', this.filters.collection || this.config.collection);
        }

        if (this.filters.search) {
          params.append('search', this.filters.search);
        }

        const apiUrl = `${AcervoXFront.api}/items?${params}`;
        console.log('AcervoX: Carregando itens de:', apiUrl);
        
        const response = await fetch(apiUrl);
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('AcervoX: Dados recebidos:', data);

        if (data && data.items) {
          this.items = data.items;
          this.renderItems();
          if (this.config.pagination && data.pages) {
            this.renderPagination(data);
          }
        } else {
          this.showEmpty();
        }
      } catch (error) {
        console.error('AcervoX: Erro ao carregar itens:', error);
        this.showError();
      } finally {
        this.loading = false;
      }
    }

    renderItems() {
      const layout = this.config.layout || 'grid';
      const columns = this.config.columns || 3;
      
      let containerClass = `acervox-${layout}`;
      if (layout !== 'list') {
        containerClass += ` columns-${columns}`;
      }

      let html = `<div class="${containerClass}">`;

      this.items.forEach(item => {
        html += this.renderItem(item);
      });

      html += '</div>';

      // Remover container anterior
      const oldContainer = this.container.querySelector(`.acervox-${layout}`);
      if (oldContainer) {
        oldContainer.remove();
      }

      // Remover loading/empty
      const loading = this.container.querySelector('.acervox-loading');
      const empty = this.container.querySelector('.acervox-empty');
      if (loading) loading.remove();
      if (empty) empty.remove();

      // Inserir novo container
      const filters = this.container.querySelector('.acervox-filters');
      if (filters) {
        filters.insertAdjacentHTML('afterend', html);
      } else {
        this.container.insertAdjacentHTML('beforeend', html);
      }

      // Setup modal
      this.setupModal();
    }

    renderItem(item) {
      const thumbnail = item.thumbnails?.large || item.thumbnails?.medium || item.thumbnail || '';
      const excerpt = this.config.show_excerpt && item.excerpt ? `<p class="acervox-item-excerpt">${this.escapeHtml(item.excerpt)}</p>` : '';
      
      let metaHTML = '';
      if (this.config.show_meta && item.meta) {
        const metaItems = Object.entries(item.meta).slice(0, 3).map(([key, meta]) => {
          const label = typeof meta === 'object' && meta !== null ? (meta.label || meta.name || key) : key;
          const value = typeof meta === 'object' && meta !== null ? (meta.value || '') : meta;
          return `<div class="acervox-meta-item"><strong>${this.escapeHtml(String(label))}:</strong> ${this.escapeHtml(String(value))}</div>`;
        }).join('');
        if (metaItems) {
          metaHTML = `<div class="acervox-item-meta">${metaItems}</div>`;
        }
      }

      return `
        <div class="acervox-item" data-item-id="${item.id}">
          <div class="acervox-item-image-wrapper">
            <img 
              src="${this.escapeHtml(thumbnail)}" 
              alt="${this.escapeHtml(item.title || '')}"
              class="acervox-item-image"
              loading="lazy"
              data-full="${this.escapeHtml(item.thumbnails?.full || thumbnail)}"
              onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%23f0f0f0\' width=\'400\' height=\'300\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3ESem imagem%3C/text%3E%3C/svg%3E'"
            />
            <div class="acervox-item-overlay">
              <div class="acervox-item-overlay-content">
                <h3>${this.escapeHtml(item.title || '')}</h3>
                ${item.excerpt ? `<p>${this.escapeHtml(item.excerpt.substring(0, 100))}...</p>` : ''}
              </div>
            </div>
          </div>
          <div class="acervox-item-content">
            <h3 class="acervox-item-title">${this.escapeHtml(item.title || '')}</h3>
            ${excerpt}
            ${metaHTML}
            <button class="acervox-btn-view-details acervox-btn acervox-btn-outline" style="margin-top: 12px; width: 100%;">
              Ver Detalhes
            </button>
          </div>
        </div>
      `;
    }

    setupModal() {
      const items = this.container.querySelectorAll('.acervox-item');
      items.forEach(itemEl => {
        const itemId = itemEl.dataset.itemId;
        const itemData = this.items.find(i => i.id == itemId);
        
        const viewBtn = itemEl.querySelector('.acervox-btn-view-details');
        if (viewBtn) {
          viewBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (itemData) {
              this.openModal(itemData);
            }
          });
        }

        // Também abrir modal ao clicar na imagem
        itemEl.addEventListener('click', (e) => {
          if (!e.target.closest('.acervox-btn-view-details') && itemData) {
            this.openModal(itemData);
          }
        });
      });
    }

    escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    openModal(item) {
      const modal = document.createElement('div');
      modal.className = 'acervox-modal active';
      
      // Construir metadados
      const metaContainer = document.createElement('div');
      metaContainer.className = 'acervox-modal-meta';
      
      if (item.meta && Object.keys(item.meta).length > 0) {
        const metaTitle = document.createElement('h4');
        metaTitle.textContent = 'Metadados';
        metaContainer.appendChild(metaTitle);
        
        const metaGrid = document.createElement('div');
        metaGrid.className = 'acervox-modal-meta-grid';
        
        Object.entries(item.meta).forEach(([key, meta]) => {
          let label, value;
          if (typeof meta === 'object' && meta !== null) {
            label = meta.label || meta.name || key;
            value = meta.value || '';
          } else {
            label = key;
            value = meta || '';
          }
          
          if (value) {
            const metaItem = document.createElement('div');
            metaItem.className = 'acervox-modal-meta-item';
            metaItem.innerHTML = `
              <strong>${this.escapeHtml(label)}:</strong>
              <span>${this.escapeHtml(String(value))}</span>
            `;
            metaGrid.appendChild(metaItem);
          }
        });
        
        metaContainer.appendChild(metaGrid);
      }

      const overlay = document.createElement('div');
      overlay.className = 'acervox-modal-overlay';
      
      const content = document.createElement('div');
      content.className = 'acervox-modal-content';
      
      const closeBtn = document.createElement('button');
      closeBtn.className = 'acervox-modal-close';
      closeBtn.textContent = '×';
      
      const body = document.createElement('div');
      body.className = 'acervox-modal-body';
      
      const imageDiv = document.createElement('div');
      imageDiv.className = 'acervox-modal-image';
      const img = document.createElement('img');
      img.src = item.thumbnails?.full || item.thumbnails?.large || item.thumbnail || '';
      img.alt = item.title;
      imageDiv.appendChild(img);
      
      const infoDiv = document.createElement('div');
      infoDiv.className = 'acervox-modal-info';
      
      const title = document.createElement('h2');
      title.className = 'acervox-modal-title';
      title.textContent = item.title;
      infoDiv.appendChild(title);
      
      if (item.excerpt) {
        const excerpt = document.createElement('div');
        excerpt.className = 'acervox-modal-excerpt';
        excerpt.textContent = item.excerpt;
        infoDiv.appendChild(excerpt);
      }
      
      if (item.content) {
        const contentText = document.createElement('div');
        contentText.className = 'acervox-modal-content-text';
        contentText.innerHTML = item.content;
        infoDiv.appendChild(contentText);
      }
      
      if (item.meta && Object.keys(item.meta).length > 0) {
        infoDiv.appendChild(metaContainer);
      }
      
      if (item.permalink) {
        const link = document.createElement('a');
        link.href = item.permalink;
        link.className = 'acervox-modal-link';
        link.target = '_blank';
        link.textContent = 'Ver página completa →';
        infoDiv.appendChild(link);
      }
      
      body.appendChild(imageDiv);
      body.appendChild(infoDiv);
      content.appendChild(closeBtn);
      content.appendChild(body);
      modal.appendChild(overlay);
      modal.appendChild(content);

      document.body.appendChild(modal);
      document.body.style.overflow = 'hidden';

      const close = () => {
        modal.remove();
        document.body.style.overflow = '';
      };

      closeBtn.addEventListener('click', close);
      overlay.addEventListener('click', close);

      document.addEventListener('keydown', function escHandler(e) {
        if (e.key === 'Escape') {
          close();
          document.removeEventListener('keydown', escHandler);
        }
      });
    }

    renderPagination(data) {
      const oldPagination = this.container.querySelector('.acervox-pagination');
      if (oldPagination) oldPagination.remove();

      if (data.pages <= 1) return;

      let html = '<div class="acervox-pagination">';

      html += `<button ${this.currentPage === 1 ? 'disabled' : ''} class="acervox-prev">‹ Anterior</button>`;

      for (let i = 1; i <= data.pages; i++) {
        if (i === 1 || i === data.pages || (i >= this.currentPage - 2 && i <= this.currentPage + 2)) {
          html += `<button ${i === this.currentPage ? 'class="active"' : ''} data-page="${i}">${i}</button>`;
        } else if (i === this.currentPage - 3 || i === this.currentPage + 3) {
          html += '<span>...</span>';
        }
      }

      html += `<button ${this.currentPage === data.pages ? 'disabled' : ''} class="acervox-next">Próxima ›</button>`;
      html += '</div>';

      this.container.insertAdjacentHTML('beforeend', html);

      this.container.querySelector('.acervox-prev')?.addEventListener('click', () => {
        if (this.currentPage > 1) {
          this.currentPage--;
          this.loadItems();
          window.scrollTo({ top: this.container.offsetTop - 100, behavior: 'smooth' });
        }
      });

      this.container.querySelector('.acervox-next')?.addEventListener('click', () => {
        if (this.currentPage < data.pages) {
          this.currentPage++;
          this.loadItems();
          window.scrollTo({ top: this.container.offsetTop - 100, behavior: 'smooth' });
        }
      });

      this.container.querySelectorAll('.acervox-pagination button[data-page]').forEach(btn => {
        btn.addEventListener('click', () => {
          this.currentPage = parseInt(btn.dataset.page);
          this.loadItems();
          window.scrollTo({ top: this.container.offsetTop - 100, behavior: 'smooth' });
        });
      });
    }

    showLoading() {
      const old = this.container.querySelector('.acervox-loading, .acervox-empty, .acervox-grid, .acervox-masonry, .acervox-list');
      if (old) old.remove();

      const loading = document.createElement('div');
      loading.className = 'acervox-loading';
      loading.innerHTML = '<div class="acervox-spinner"></div>';
      
      const filters = this.container.querySelector('.acervox-filters');
      if (filters) {
        filters.insertAdjacentElement('afterend', loading);
      } else {
        this.container.appendChild(loading);
      }
    }

    showEmpty() {
      const old = this.container.querySelector('.acervox-loading, .acervox-grid, .acervox-masonry, .acervox-list');
      if (old) old.remove();

      const empty = document.createElement('div');
      empty.className = 'acervox-empty';
      empty.innerHTML = `
        <div class="acervox-empty-icon">📭</div>
        <h3>Nenhum item encontrado</h3>
        <p>Tente ajustar os filtros ou verificar se há itens cadastrados.</p>
      `;

      const filters = this.container.querySelector('.acervox-filters');
      if (filters) {
        filters.insertAdjacentElement('afterend', empty);
      } else {
        this.container.appendChild(empty);
      }
    }

    showError() {
      const old = this.container.querySelector('.acervox-loading');
      if (old) {
        old.innerHTML = `
          <div style="text-align: center;">
            <div style="font-size: 48px; margin-bottom: 16px;">⚠️</div>
            <h3>Erro ao carregar itens</h3>
            <p>Tente recarregar a página.</p>
          </div>
        `;
      }
    }
  }
})();
