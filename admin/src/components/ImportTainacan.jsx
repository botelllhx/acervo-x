import React, { useState, useEffect } from 'react';
import { Database, Download, CheckCircle2, XCircle, AlertCircle } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/Card';
import { Button } from './ui/Button';
import { Select } from './ui/Select';

export default function ImportTainacan() {
  const [collections, setCollections] = useState([]);
  const [selectedCollection, setSelectedCollection] = useState('');
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState(null);
  const [loadingCollections, setLoadingCollections] = useState(true);

  useEffect(() => {
    fetch('/wp-json/acervox/v1/tainacan/collections', {
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
            message: 'Tainacan não está ativo ou não foi encontrado',
            isWarning: true
          });
        }
      })
      .catch(() => {
        setResult({
          success: false,
          message: 'Erro ao verificar Tainacan',
          isWarning: true
        });
      })
      .finally(() => setLoadingCollections(false));
  }, []);

  const importNow = async () => {
    if (!selectedCollection) {
      alert('Selecione uma coleção');
      return;
    }

    setLoading(true);
    setResult(null);

    try {
      const response = await fetch('/wp-json/acervox/v1/import/tainacan', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': AcervoX?.nonce || ''
        },
        body: JSON.stringify({
          tainacan_collection: parseInt(selectedCollection)
        })
      });

      const data = await response.json();

      if (response.ok) {
        setResult({
          success: true,
          message: `Importação concluída com sucesso! ${data.log?.length || 0} itens processados.`,
          data: data
        });
        setSelectedCollection('');
      } else {
        setResult({
          success: false,
          message: data.message || 'Erro na importação',
          data: data
        });
      }
    } catch (error) {
      setResult({
        success: false,
        message: 'Erro ao conectar com a API',
        error: error.message
      });
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <div className="acervox-header">
        <h1 className="acervox-header-title">Importar do Tainacan</h1>
      </div>
      <div className="acervox-content">
        <Card>
          <CardHeader>
            <CardTitle>Importar Coleção</CardTitle>
            <CardDescription>
              Importe coleções e itens do plugin Tainacan para o AcervoX
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
                  <label className="acervox-form-label">Selecione uma coleção do Tainacan</label>
                  <Select
                    value={selectedCollection}
                    onChange={(e) => setSelectedCollection(e.target.value)}
                    disabled={loading}
                  >
                    <option value="">Selecione uma coleção</option>
                    {collections.map(col => (
                      <option key={col.id} value={col.id}>
                        {col.title || col.name || `Coleção #${col.id}`}
                      </option>
                    ))}
                  </Select>
                </div>

                <Button
                  onClick={importNow}
                  disabled={loading || !selectedCollection}
                  style={{ marginTop: '16px' }}
                >
                  {loading ? (
                    <>
                      <div className="acervox-spinner" style={{ width: '16px', height: '16px', borderWidth: '2px', marginRight: '8px' }}></div>
                      Importando...
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
                  <strong>Nenhuma coleção Tainacan encontrada</strong>
                  <p style={{ margin: '8px 0 0 0', fontSize: '14px' }}>
                    Verifique se o plugin Tainacan está ativo e possui coleções cadastradas.
                  </p>
                </div>
              </div>
            )}

            {result && !result.isWarning && (
              <div className={`acervox-alert ${result.success ? 'acervox-alert-success' : 'acervox-alert-error'}`} style={{ marginTop: '24px' }}>
                {result.success ? <CheckCircle2 size={20} /> : <XCircle size={20} />}
                <div style={{ flex: 1 }}>
                  <strong>{result.message}</strong>
                  {result.data?.log && result.data.log.length > 0 && (
                    <details style={{ marginTop: '12px' }}>
                      <summary style={{ cursor: 'pointer', fontSize: '14px', marginBottom: '8px' }}>
                        Ver detalhes da importação ({result.data.log.length} itens)
                      </summary>
                      <ul style={{ marginLeft: '20px', fontSize: '13px', lineHeight: '1.8' }}>
                        {result.data.log.slice(0, 20).map((log, idx) => (
                          <li key={idx}>{log}</li>
                        ))}
                        {result.data.log.length > 20 && (
                          <li style={{ fontStyle: 'italic', color: 'hsl(var(--muted-foreground))' }}>
                            ... e mais {result.data.log.length - 20} itens
                          </li>
                        )}
                      </ul>
                    </details>
                  )}
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </>
  );
}
