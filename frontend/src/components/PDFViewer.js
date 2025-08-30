import React, { useState, useRef, useEffect } from 'react';

const PDFViewer = ({ 
  documentUrl, 
  onPositionSelect, 
  selectedPosition, 
  signatureType = 'digital',
  signaturePreview = null 
}) => {
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [pdfUrl, setPdfUrl] = useState(null);
  const iframeRef = useRef(null);
  const overlayRef = useRef(null);
  const [overlayDimensions, setOverlayDimensions] = useState({ width: 0, height: 0 });

  // Carregar PDF real da API
  useEffect(() => {
    if (!documentUrl) return;

    const loadPDF = async () => {
      try {
        setIsLoading(true);
        setError(null);

        // Usar a URL do documento diretamente
        setPdfUrl(documentUrl);
        
        // Definir posição padrão da assinatura
        if (!selectedPosition && onPositionSelect) {
          const defaultPosition = {
            x: 445, // Posição X no PDF (595 - 150)
            y: 100, // Posição Y no PDF
            page: 1,
            screenX: 445,
            screenY: 100
          };
          
          setTimeout(() => {
            onPositionSelect(defaultPosition);
          }, 1000);
        }
        
      } catch (err) {
        console.error('Erro ao carregar PDF:', err);
        setError(err.message);
      } finally {
        setIsLoading(false);
      }
    };

    loadPDF();
  }, [documentUrl, selectedPosition, onPositionSelect]);

  // Atualizar dimensões do overlay quando o iframe carregar
  useEffect(() => {
    const handleIframeLoad = () => {
      if (iframeRef.current && overlayRef.current) {
        const rect = iframeRef.current.getBoundingClientRect();
        setOverlayDimensions({ width: rect.width, height: rect.height });
      }
    };

    const iframe = iframeRef.current;
    if (iframe) {
      iframe.addEventListener('load', handleIframeLoad);
      return () => iframe.removeEventListener('load', handleIframeLoad);
    }
  }, [pdfUrl]);

  // Função para lidar com cliques no overlay
  const handleOverlayClick = (event) => {
    if (!overlayRef.current || !onPositionSelect) return;

    const rect = overlayRef.current.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;

    // Converter coordenadas da tela para coordenadas do PDF (assumindo tamanho A4)
    const pdfWidth = 595; // Largura padrão A4 em pontos
    const pdfHeight = 842; // Altura padrão A4 em pontos
    
    const pdfX = (x / rect.width) * pdfWidth;
    const pdfY = pdfHeight - (y / rect.height) * pdfHeight; // Inverter Y para coordenadas PDF

    onPositionSelect({
      x: pdfX,
      y: pdfY,
      page: 1,
      screenX: x,
      screenY: y
    });
  };

  const renderSignaturePreview = () => {
    if (!selectedPosition) return null;

    const previewStyle = {
      position: 'absolute',
      left: `${selectedPosition.screenX - 100}px`,
      top: `${selectedPosition.screenY - 35}px`,
      width: '200px',
      height: '70px',
      border: '2px solid #007bff',
      backgroundColor: 'rgba(0, 123, 255, 0.05)',
      borderRadius: '8px',
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      justifyContent: 'center',
      fontSize: '11px',
      color: '#007bff',
      fontWeight: '600',
      pointerEvents: 'none',
      zIndex: 10,
      boxShadow: '0 2px 8px rgba(0, 123, 255, 0.2)'
    };

    return (
      <div style={previewStyle}>
        <div style={{ fontSize: '16px', marginBottom: '4px' }}>
          {signatureType === 'digital' ? '🔒' : '✍️'}
        </div>
        <div style={{ fontSize: '10px', textAlign: 'center', lineHeight: '1.2' }}>
          {signatureType === 'digital' ? 'Assinatura Digital' : 'Assinatura Eletrônica'}
        </div>
        <div style={{ fontSize: '9px', opacity: 0.7, marginTop: '2px' }}>
          Página {selectedPosition.page}
        </div>
      </div>
    );
  };

  if (isLoading) {
    return (
      <div style={{
        border: '1px solid #ddd',
        borderRadius: '8px',
        padding: '40px',
        textAlign: 'center',
        backgroundColor: '#f8f9fa'
      }}>
        <div style={{ fontSize: '16px', color: '#666', marginBottom: '10px' }}>
          📄 Carregando documento...
        </div>
        <div style={{ fontSize: '12px', color: '#999' }}>
          Aguarde enquanto o documento é carregado
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div style={{
        border: '1px solid #dc3545',
        borderRadius: '8px',
        padding: '40px',
        textAlign: 'center',
        backgroundColor: '#f8d7da',
        color: '#721c24'
      }}>
        <div style={{ fontSize: '16px', marginBottom: '10px' }}>
          ❌ {error}
        </div>
        <div style={{ fontSize: '12px' }}>
          Erro ao carregar o documento
        </div>
      </div>
    );
  }

  return (
    <div style={{
      border: '1px solid #ddd',
      borderRadius: '8px',
      overflow: 'hidden',
      backgroundColor: '#f8f9fa'
    }}>
      {/* Instruções */}
      <div style={{
        padding: '12px',
        backgroundColor: '#e3f2fd',
        fontSize: '12px',
        color: '#1565c0',
        textAlign: 'center',
        lineHeight: '1.4'
      }}>
        <div style={{ marginBottom: '4px' }}>
          📍 Clique no documento para escolher onde posicionar a assinatura
        </div>
        <div style={{ fontSize: '11px', opacity: 0.8 }}>
          💡 O documento real será exibido abaixo
        </div>
      </div>

      {/* Visualizador PDF */}
      <div style={{
        position: 'relative',
        minHeight: '600px',
        maxHeight: '800px',
        backgroundColor: '#ffffff'
      }}>
        {pdfUrl && (
          <>
            {/* Iframe com o PDF real */}
            <iframe
              ref={iframeRef}
              src={pdfUrl}
              style={{
                width: '100%',
                height: '100%',
                minHeight: '600px',
                border: 'none',
                display: 'block'
              }}
              title="Documento PDF"
            />
            
            {/* Overlay transparente para capturar cliques */}
            <div
              ref={overlayRef}
              onClick={handleOverlayClick}
              style={{
                position: 'absolute',
                top: 0,
                left: 0,
                width: '100%',
                height: '100%',
                backgroundColor: 'transparent',
                cursor: 'crosshair',
                zIndex: 5
              }}
            />
            
            {/* Preview da assinatura */}
            {renderSignaturePreview()}
          </>
        )}
      </div>

      {/* Informações da posição selecionada */}
      {selectedPosition && (
        <div style={{
          padding: '8px 12px',
          backgroundColor: '#d4edda',
          fontSize: '12px',
          color: '#155724',
          textAlign: 'center'
        }}>
          ✅ Posição selecionada: Página {selectedPosition.page},
          X: {Math.round(selectedPosition.x)}, Y: {Math.round(selectedPosition.y)}
        </div>
      )}
    </div>
  );
};

export default PDFViewer;