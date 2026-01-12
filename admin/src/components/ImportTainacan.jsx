import React, { useState, useEffect } from 'react';
import { Database, Download, CheckCircle2, XCircle, AlertCircle, Archive, Loader2 } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/Card';
import { Button } from './ui/Button';
import { Select } from './ui/Select';
import { useToast } from './ToastProvider';

export default function ImportTainacan() {
  const [collections, setCollections] = useState([]);
  const [selectedCollection, setSelectedCollection] = useState('');
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState(null);
  const [loadingCollections, setLoadingCollections] = useState(true);
  const { showToast } = useToast();

  useEffect(() => {
    fetch('/wp-json/acervox/v1/external/collections', {
      headers: {
        'X-WP-Nonce': AcervoX?.nonce || ''
      }
    })
      .then(res => res.json())
      .then(data => {
        if (data.collections && Array.isArray(data.collections)) {
          setCollections(data.collections);
        }
        if (!data.active) {
          setResult({
            success: false,
            message: 'Sistema externo não está ativo ou não foi encontrado',
            isWarning: true
          });
        }
      })
      .catch(() => {
        setResult({
          success: false,
          message: 'Erro ao verificar sistema externo',
          isWarning: true
        });
      })
      .finally(() => setLoadingCollections(false));
  }, []);

  const importNow = async () => {
    if (!selectedCollection) {
      setResult({
        success: false,
        message: 'Por favor, selecione uma coleção antes de importar',
        isWarning: true
      });
      return;
    }

    setLoading(true);
    setResult(null);

    try {
      const response = await fetch('/wp-json/acervox/v1/import/external', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': AcervoX?.nonce || ''
        },
        body: JSON.stringify({
          external_collection: parseInt(selectedCollection)
        })
      });

      const data = await response.json();

      if (response.ok) {
        setResult({
          success: true,
          message: `Importação concluída com sucesso! ${data.imported_count || data.log?.length || 0} itens processados.`,
          data: data
        });
        setSelectedCollection('');
        showToast(
          `Importação concluída! ${data.imported_count || 0} itens importados${data.failed_count > 0 ? ` (${data.failed_count} falhas)` : ''}`,
          data.failed_count > 0 ? 'warning' : 'success',
          5000
        );
      } else {
        const errorMsg = data.message || 'Erro na importação';
        setResult({
          success: false,
          message: errorMsg,
          data: data
        });
        showToast(errorMsg, 'error', 5000);
      }
    } catch (error) {
      const errorMsg = 'Erro ao conectar com a API: ' + error.message;
      setResult({
        success: false,
        message: errorMsg,
        error: error.message
      });
      showToast(errorMsg, 'error', 5000);
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <div className="acervox-header">
        <h1 className="acervox-header-title">Importar de Sistema Externo</h1>
      </div>
      <div className="acervox-content">
        <Card>
          <CardHeader>
            <CardTitle>Importar Coleção</CardTitle>
            <CardDescription>
              Importe coleções e itens de sistemas externos de gestão de acervos para o AcervoX
            </CardDescription>
          </CardHeader>
          <CardContent>
            {loadingCollections ? (
              <div className="acervox-loading">
                <div className="acervox-spinner"></div>
              </div>
            ) : collections.length > 0 ? (
              <>
                <div className="acervox-form-group">
                  <label className="acervox-form-label">
                    Coleção <span style={{ color: 'hsl(var(--destructive))' }}>*</span>
                  </label>
                  <Select
                    value={selectedCollection}
                    onChange={(e) => {
                      setSelectedCollection(e.target.value);
                      setResult(null);
                    }}
                    disabled={loading}
                    style={{
                      borderColor: !selectedCollection && loading ? 'hsl(var(--destructive))' : 'hsl(var(--border))'
                    }}
                  >
                    <option value="">-- Selecione uma coleção --</option>
                    {collections.map(col => (
                      <option key={col.id} value={col.id}>
                        {col.title || col.name || `Coleção #${col.id}`}
                      </option>
                    ))}
                  </Select>
                  {!selectedCollection && (
                    <p style={{ marginTop: '8px', fontSize: '13px', color: 'hsl(var(--destructive))', display: 'flex', alignItems: 'center', gap: '4px' }}>
                      <AlertCircle size={14} />
                      Selecione uma coleção para continuar
                    </p>
                  )}
                </div>

                {result && result.isWarning && (
                  <div className="acervox-alert acervox-alert-warning" style={{ marginBottom: '16px' }}>
                    <AlertCircle size={20} />
                    <div>
                      <strong>{result.message}</strong>
                    </div>
                  </div>
                )}

                <Button
                  onClick={importNow}
                  disabled={loading || !selectedCollection}
                  style={{ 
                    marginTop: '16px',
                    width: '100%',
                    opacity: (!selectedCollection) ? 0.5 : 1,
                    cursor: (!selectedCollection) ? 'not-allowed' : 'pointer'
                  }}
                >
                  {loading ? (
                    <>
                      <Loader2 size={16} className="spinning" />
                      Importando coleção...
                    </>
                  ) : (
                    <>
                      <Download size={16} />
                      Importar Coleção
                    </>
                  )}
                </Button>
              </>
            ) : (
              <div className={`acervox-alert ${result?.isWarning ? 'acervox-alert-warning' : 'acervox-alert-error'}`}>
                <AlertCircle size={20} />
                <div>
                  <strong>Nenhuma coleção externa encontrada</strong>
                  <p style={{ margin: '8px 0 0 0', fontSize: '14px' }}>
                    Verifique se o sistema externo está ativo e possui coleções cadastradas.
                  </p>
                </div>
              </div>
            )}

            {result && !result.isWarning && (
              <div className={`acervox-alert ${result.success ? 'acervox-alert-success' : 'acervox-alert-error'}`} style={{ marginTop: '24px' }}>
                {result.success ? <CheckCircle2 size={20} /> : <XCircle size={20} />}
                <div style={{ flex: 1 }}>
                  <strong>{result.message}</strong>
                  {result.success && result.data?.collection_id && (
                    <div style={{ marginTop: '12px', display: 'flex', alignItems: 'center', gap: '8px', fontSize: '14px' }}>
                      <Archive size={16} />
                      <span>Coleção criada com ID: <strong>{result.data.collection_id}</strong></span>
                    </div>
                  )}
                  {result.success && result.data && (
                    <div style={{ marginTop: '12px', fontSize: '14px', display: 'flex', flexDirection: 'column', gap: '8px' }}>
                      {result.data.imported_count !== undefined && (
                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                          <CheckCircle2 size={16} style={{ color: 'hsl(142, 76%, 36%)' }} />
                          <span><strong>{result.data.imported_count}</strong> itens importados com sucesso</span>
                        </div>
                      )}
                      {result.data.failed_count !== undefined && result.data.failed_count > 0 && (
                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                          <XCircle size={16} style={{ color: 'hsl(0, 84%, 60%)' }} />
                          <span><strong>{result.data.failed_count}</strong> itens falharam</span>
                        </div>
                      )}
                    </div>
                  )}
                  {result.data?.log && result.data.log.length > 0 && (
                    <details style={{ marginTop: '12px' }}>
                      <summary style={{ cursor: 'pointer', fontSize: '14px', marginBottom: '8px', fontWeight: 500 }}>
                        Ver detalhes da importação ({result.data.log.length} entradas)
                      </summary>
                      <div style={{
                        marginTop: '8px',
                        maxHeight: '200px',
                        overflowY: 'auto',
                        padding: '12px',
                        background: 'hsl(var(--muted))',
                        borderRadius: '6px',
                        fontSize: '13px',
                        fontFamily: 'monospace',
                        lineHeight: '1.6'
                      }}>
                        {result.data.log.slice(0, 50).map((log, idx) => (
                          <div key={idx} style={{ marginBottom: '4px' }}>{log}</div>
                        ))}
                        {result.data.log.length > 50 && (
                          <div style={{ fontStyle: 'italic', color: 'hsl(var(--muted-foreground))', marginTop: '8px' }}>
                            ... e mais {result.data.log.length - 50} entradas
                          </div>
                        )}
                      </div>
                    </details>
                  )}
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
      <style>{`
        .spinning {
          animation: spin 1s linear infinite;
        }
        @keyframes spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
      `}</style>
    </>
  );
}
