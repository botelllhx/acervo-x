import React, { useState, useEffect } from 'react';
import { Image, X, GripVertical, Plus, Video, FileText, Trash2 } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from './ui/Card';
import { Button } from './ui/Button';
import { useToast } from './ToastProvider';

export default function ItemGallery({ itemId, onClose }) {
  const [gallery, setGallery] = useState([]);
  const [loading, setLoading] = useState(true);
  const [reordering, setReordering] = useState(false);
  const { showToast } = useToast();

  useEffect(() => {
    loadGallery();
  }, [itemId]);

  const loadGallery = async () => {
    setLoading(true);
    try {
      const response = await fetch(`/wp-json/acervox/v1/items/${itemId}/gallery`);
      const data = await response.json();
      if (data.success) {
        setGallery(data.gallery || []);
      }
    } catch (error) {
      console.error('Erro ao carregar galeria', error);
    } finally {
      setLoading(false);
    }
  };

  const handleRemove = async (attachmentId) => {
    if (!confirm('Tem certeza que deseja remover esta mídia da galeria?')) {
      return;
    }

    try {
      const response = await fetch(`/wp-json/acervox/v1/items/${itemId}/gallery/${attachmentId}`, {
        method: 'DELETE',
        headers: {
          'X-WP-Nonce': AcervoX?.nonce || ''
        }
      });

      const data = await response.json();
      if (response.ok && data.success) {
        showToast('Mídia removida com sucesso!', 'success');
        loadGallery();
      } else {
        showToast('Erro ao remover mídia', 'error');
      }
    } catch (error) {
      console.error('Erro ao remover mídia', error);
      showToast('Erro ao remover mídia. Tente novamente.', 'error');
    }
  };

  const handleAddFromMediaLibrary = () => {
    // Abrir Media Library do WordPress
    const mediaUploader = wp.media({
      title: 'Selecionar Mídia',
      button: {
        text: 'Adicionar à Galeria'
      },
      multiple: true
    });

    mediaUploader.on('select', async () => {
      const attachments = mediaUploader.state().get('selection').toJSON();
      
      for (const attachment of attachments) {
        try {
          const response = await fetch(`/wp-json/acervox/v1/items/${itemId}/gallery`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-WP-Nonce': AcervoX?.nonce || ''
            },
            body: JSON.stringify({
              attachment_id: attachment.id,
              type: attachment.type === 'image' ? 'image' : 
                    attachment.type === 'video' ? 'video' : 'document'
            })
          });

          const data = await response.json();
          if (!response.ok || !data.success) {
            throw new Error(data.message || 'Erro ao adicionar mídia');
          }
        } catch (error) {
          console.error('Erro ao adicionar mídia', error);
          showToast(`Erro ao adicionar mídia: ${attachment.title}`, 'error');
        }
      }

      showToast(`${attachments.length} mídia(s) adicionada(s) com sucesso!`, 'success');
      loadGallery();
    });

    mediaUploader.open();
  };

  const handleReorder = async (newOrder) => {
    const attachmentIds = newOrder.map(item => item.id);
    
    try {
      const response = await fetch(`/wp-json/acervox/v1/items/${itemId}/gallery/order`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': AcervoX?.nonce || ''
        },
        body: JSON.stringify({
          attachment_ids: attachmentIds
        })
      });

      const data = await response.json();
      if (response.ok && data.success) {
        setGallery(data.gallery || []);
        showToast('Ordem atualizada com sucesso!', 'success');
      }
    } catch (error) {
      console.error('Erro ao reordenar', error);
      showToast('Erro ao atualizar ordem', 'error');
    }
  };

  const moveItem = (fromIndex, toIndex) => {
    const newGallery = [...gallery];
    const [moved] = newGallery.splice(fromIndex, 1);
    newGallery.splice(toIndex, 0, moved);
    setGallery(newGallery);
    handleReorder(newGallery);
  };

  if (loading) {
    return (
      <div style={{ padding: '40px', textAlign: 'center' }}>
        <div className="acervox-spinner" style={{ margin: '0 auto' }}></div>
      </div>
    );
  }

  return (
    <div style={{ padding: '24px' }}>
      <div style={{ marginBottom: '20px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <div>
          <h2 style={{ fontSize: '18px', fontWeight: 600, marginBottom: '4px' }}>Galeria de Mídia</h2>
          <p style={{ fontSize: '14px', color: 'hsl(var(--muted-foreground))' }}>
            Gerencie imagens, vídeos e documentos deste item
          </p>
        </div>
        {onClose && (
          <Button variant="ghost" size="sm" onClick={onClose}>
            <X size={16} />
          </Button>
        )}
      </div>
      <div>
        <div style={{ marginBottom: '20px' }}>
          <Button onClick={handleAddFromMediaLibrary}>
            <Plus size={16} style={{ marginRight: '8px' }} />
            Adicionar Mídia
          </Button>
        </div>

        {gallery.length === 0 ? (
          <div className="acervox-empty" style={{ padding: '40px' }}>
            <div className="acervox-empty-icon">
              <Image size={48} />
            </div>
            <div className="acervox-empty-title">Nenhuma mídia na galeria</div>
            <div className="acervox-empty-description">
              Adicione imagens, vídeos ou documentos à galeria deste item
            </div>
          </div>
        ) : (
          <div style={{ 
            display: 'grid', 
            gridTemplateColumns: 'repeat(auto-fill, minmax(150px, 1fr))', 
            gap: '16px' 
          }}>
            {gallery.map((media, index) => (
              <div
                key={media.id}
                style={{
                  position: 'relative',
                  background: 'hsl(var(--muted))',
                  borderRadius: '8px',
                  overflow: 'hidden',
                  border: '1px solid hsl(var(--border))',
                  cursor: reordering ? 'move' : 'default'
                }}
              >
                {media.type === 'image' && media.thumbnails?.medium ? (
                  <img
                    src={media.thumbnails.medium}
                    alt={media.title}
                    style={{
                      width: '100%',
                      height: '150px',
                      objectFit: 'cover',
                      display: 'block'
                    }}
                  />
                ) : media.type === 'video' ? (
                  <div style={{
                    width: '100%',
                    height: '150px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    background: 'hsl(var(--muted))'
                  }}>
                    <Video size={32} style={{ color: 'hsl(var(--muted-foreground))' }} />
                  </div>
                ) : (
                  <div style={{
                    width: '100%',
                    height: '150px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    background: 'hsl(var(--muted))'
                  }}>
                    <FileText size={32} style={{ color: 'hsl(var(--muted-foreground))' }} />
                  </div>
                )}
                
                <div style={{
                  position: 'absolute',
                  top: '8px',
                  right: '8px',
                  display: 'flex',
                  gap: '4px'
                }}>
                  <Button
                    variant="ghost"
                    size="icon"
                    style={{
                      background: 'rgba(255, 255, 255, 0.9)',
                      width: '28px',
                      height: '28px',
                      padding: 0
                    }}
                    onClick={() => handleRemove(media.id)}
                    title="Remover"
                  >
                    <Trash2 size={14} style={{ color: 'hsl(var(--destructive))' }} />
                  </Button>
                </div>

                <div style={{
                  padding: '8px',
                  fontSize: '12px',
                  color: 'hsl(var(--muted-foreground))',
                  overflow: 'hidden',
                  textOverflow: 'ellipsis',
                  whiteSpace: 'nowrap'
                }}>
                  {media.title || `Mídia ${index + 1}`}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
