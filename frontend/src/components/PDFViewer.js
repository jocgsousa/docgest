import React, { useState, useRef, useEffect } from 'react';
import { Document, Page, pdfjs } from 'react-pdf';

// Configurar worker do PDF.js
pdfjs.GlobalWorkerOptions.workerSrc = `//cdnjs.cloudflare.com/ajax/libs/pdf.js/${pdfjs.version}/pdf.worker.min.js`;

const PDFViewer = ({ 
  documentUrl, 
  onPositionSelect, 
  selectedPosition, 
  signatureType = 'digital',
  signaturePreview = null 
}) => {
  const [numPages, setNumPages] = useState(null);
  const [pageNumber, setPageNumber] = useState(1);
  const [scale, setScale] = useState(1.0);
  const [pageSize, setPageSize] = useState({ width: 0, height: 0 });
  const containerRef = useRef(null);
  const pageRef = useRef(null);

  const onDocumentLoadSuccess = ({ numPages }) => {
    setNumPages(numPages);
  };

  const onPageLoadSuccess = (page) => {
    const { width, height } = page.getViewport({ scale: 1 });
    setPageSize({ width, height });
  };

  // Adicionar suporte a atalhos de teclado
  useEffect(() => {
    const handleKeyDown = (event) => {
      if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') {
        return; // Não interferir em campos de entrada
      }
      
      switch (event.key) {
        case 'ArrowLeft':
          if (pageNumber > 1) {
            setPageNumber(prev => prev - 1);
            event.preventDefault();
          }
          break;
        case 'ArrowRight':
          if (pageNumber < (numPages || 1)) {
            setPageNumber(prev => prev + 1);
            event.preventDefault();
          }
          break;
        case '+':
        case '=':
          setScale(prev => Math.min(2.0, prev + 0.1));
          event.preventDefault();
          break;
        case '-':
          setScale(prev => Math.max(0.5, prev - 0.1));
          event.preventDefault();
          break;
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [pageNumber, numPages]);

  const handlePageClick = (event) => {
    if (!pageRef.current) return;

    const rect = pageRef.current.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;

    // Converter coordenadas da tela para coordenadas do PDF
    const pdfX = (x / rect.width) * pageSize.width;
    const pdfY = pageSize.height - (y / rect.height) * pageSize.height; // PDF usa origem no canto inferior esquerdo

    onPositionSelect({
      x: pdfX,
      y: pdfY,
      page: pageNumber,
      screenX: x,
      screenY: y
    });
  };

  const renderSignaturePreview = () => {
    if (!selectedPosition || selectedPosition.page !== pageNumber) return null;

    const previewStyle = {
      position: 'absolute',
      left: `${selectedPosition.screenX - 100}px`, // Centralizar preview (200px / 2)
      top: `${selectedPosition.screenY - 35}px`, // Centralizar preview (70px / 2)
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
      boxShadow: '0 2px 8px rgba(0, 123, 255, 0.2)',
      backdropFilter: 'blur(2px)'
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

  return (
    <div style={{ 
      border: '1px solid #ddd', 
      borderRadius: '8px', 
      overflow: 'hidden',
      backgroundColor: '#f8f9fa'
    }}>
      {/* Controles */}
      <div style={{
        padding: '12px',
        borderBottom: '1px solid #ddd',
        backgroundColor: '#fff',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        flexWrap: 'wrap',
        gap: '12px'
      }}>
        {/* Indicador de múltiplas páginas */}
        {numPages > 1 && (
          <div style={{
            fontSize: '12px',
            color: '#6c757d',
            backgroundColor: '#f8f9fa',
            padding: '4px 8px',
            borderRadius: '12px',
            border: '1px solid #e9ecef'
          }}>
            📄 {numPages} páginas
          </div>
        )}
        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
          <button
            onClick={() => setPageNumber(prev => Math.max(1, prev - 1))}
            disabled={pageNumber <= 1}
            title="Página anterior (←)"
            style={{
              padding: '6px 12px',
              border: '1px solid #ddd',
              borderRadius: '6px',
              backgroundColor: pageNumber <= 1 ? '#f8f9fa' : '#fff',
              cursor: pageNumber <= 1 ? 'not-allowed' : 'pointer',
              fontSize: '14px',
              fontWeight: '500',
              transition: 'all 0.2s ease'
            }}
          >
            ← Anterior
          </button>
          <div style={{ 
            fontSize: '14px', 
            minWidth: '100px', 
            textAlign: 'center',
            fontWeight: '600',
            color: '#495057'
          }}>
            {pageNumber} de {numPages || '?'}
          </div>
          <button
            onClick={() => setPageNumber(prev => Math.min(numPages || 1, prev + 1))}
            disabled={pageNumber >= (numPages || 1)}
            title="Próxima página (→)"
            style={{
              padding: '6px 12px',
              border: '1px solid #ddd',
              borderRadius: '6px',
              backgroundColor: pageNumber >= (numPages || 1) ? '#f8f9fa' : '#fff',
              cursor: pageNumber >= (numPages || 1) ? 'not-allowed' : 'pointer',
              fontSize: '14px',
              fontWeight: '500',
              transition: 'all 0.2s ease'
            }}
          >
            Próxima →
          </button>
        </div>

        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
          <button
            onClick={() => setScale(prev => Math.max(0.5, prev - 0.1))}
            style={{
              padding: '4px 8px',
              border: '1px solid #ddd',
              borderRadius: '4px',
              backgroundColor: '#fff',
              cursor: 'pointer'
            }}
          >
            -
          </button>
          <span style={{ fontSize: '14px', minWidth: '60px', textAlign: 'center' }}>
            {Math.round(scale * 100)}%
          </span>
          <button
            onClick={() => setScale(prev => Math.min(2.0, prev + 0.1))}
            style={{
              padding: '4px 8px',
              border: '1px solid #ddd',
              borderRadius: '4px',
              backgroundColor: '#fff',
              cursor: 'pointer'
            }}
          >
            +
          </button>
        </div>
      </div>

      {/* Instruções */}
      <div style={{
        padding: '8px 12px',
        backgroundColor: '#e3f2fd',
        fontSize: '12px',
        color: '#1565c0',
        textAlign: 'center',
        lineHeight: '1.4'
      }}>
        <div style={{ marginBottom: '4px' }}>
          📍 Clique no documento para escolher onde posicionar a assinatura
        </div>
        {numPages > 1 && (
          <div style={{ fontSize: '11px', opacity: 0.8 }}>
            💡 Use as setas ← → do teclado para navegar entre páginas | + - para zoom
          </div>
        )}
      </div>

      {/* Visualizador do PDF */}
      <div 
        ref={containerRef}
        style={{ 
          position: 'relative',
          display: 'flex', 
          justifyContent: 'center',
          padding: '20px',
          minHeight: '400px',
          maxHeight: '600px',
          overflow: 'auto'
        }}
      >
        <Document
          file={documentUrl}
          onLoadSuccess={onDocumentLoadSuccess}
          loading={
            <div style={{ 
              display: 'flex', 
              alignItems: 'center', 
              justifyContent: 'center',
              height: '200px',
              color: '#666'
            }}>
              Carregando documento...
            </div>
          }
          error={
            <div style={{ 
              display: 'flex', 
              alignItems: 'center', 
              justifyContent: 'center',
              height: '200px',
              color: '#dc3545'
            }}>
              Erro ao carregar o documento
            </div>
          }
        >
          <div 
            ref={pageRef}
            style={{ position: 'relative', cursor: 'crosshair' }}
            onClick={handlePageClick}
          >
            <Page 
              pageNumber={pageNumber}
              scale={scale}
              onLoadSuccess={onPageLoadSuccess}
            />
            {renderSignaturePreview()}
          </div>
        </Document>
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