import React, { useRef, useState, useEffect } from 'react';
import styled from 'styled-components';
import Button from './Button';

const SignaturePadContainer = styled.div`
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  background: white;
  position: relative;
  margin-bottom: 16px;
`;

const Canvas = styled.canvas`
  display: block;
  cursor: crosshair;
  touch-action: none;
  width: 100%;
  height: 200px;
`;

const ControlsContainer = styled.div`
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: #f9fafb;
  border-top: 1px solid #e5e7eb;
  border-radius: 0 0 6px 6px;
`;

const TypedSignatureContainer = styled.div`
  padding: 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  background: white;
  margin-bottom: 16px;
`;

const TypedSignatureInput = styled.input`
  width: 100%;
  padding: 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 24px;
  text-align: center;
  margin-bottom: 12px;
  
  &.font-cursive {
    font-family: 'Brush Script MT', cursive;
  }
  
  &.font-elegant {
    font-family: 'Lucida Handwriting', cursive;
  }
  
  &.font-modern {
    font-family: 'Segoe Script', cursive;
  }
`;

const FontSelector = styled.select`
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
`;

const ModeSelector = styled.div`
  display: flex;
  gap: 16px;
  margin-bottom: 16px;
`;

const ModeButton = styled.button`
  padding: 8px 16px;
  border: 2px solid ${props => props.active ? '#3b82f6' : '#e5e7eb'};
  background: ${props => props.active ? '#3b82f6' : 'white'};
  color: ${props => props.active ? 'white' : '#374151'};
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
  
  &:hover {
    border-color: #3b82f6;
    background: ${props => props.active ? '#2563eb' : '#f3f4f6'};
  }
`;

const SignaturePad = ({ onSignatureChange, value }) => {
  const canvasRef = useRef(null);
  const [isDrawing, setIsDrawing] = useState(false);
  const [mode, setMode] = useState('draw'); // 'draw' ou 'type'
  const [typedSignature, setTypedSignature] = useState('');
  const [selectedFont, setSelectedFont] = useState('cursive');
  const [isEmpty, setIsEmpty] = useState(true);

  useEffect(() => {
    const canvas = canvasRef.current;
    if (canvas) {
      const ctx = canvas.getContext('2d');
      ctx.strokeStyle = '#000000';
      ctx.lineWidth = 2;
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      
      // Set canvas size
      const rect = canvas.getBoundingClientRect();
      canvas.width = rect.width * window.devicePixelRatio;
      canvas.height = rect.height * window.devicePixelRatio;
      ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
    }
  }, []);

  const getEventPos = (e) => {
    const canvas = canvasRef.current;
    const rect = canvas.getBoundingClientRect();
    const clientX = e.clientX || (e.touches && e.touches[0].clientX);
    const clientY = e.clientY || (e.touches && e.touches[0].clientY);
    
    return {
      x: clientX - rect.left,
      y: clientY - rect.top
    };
  };

  const startDrawing = (e) => {
    e.preventDefault();
    setIsDrawing(true);
    const pos = getEventPos(e);
    const ctx = canvasRef.current.getContext('2d');
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
  };

  const draw = (e) => {
    if (!isDrawing) return;
    e.preventDefault();
    
    const pos = getEventPos(e);
    const ctx = canvasRef.current.getContext('2d');
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
    
    setIsEmpty(false);
    updateSignature();
  };

  const stopDrawing = (e) => {
    e.preventDefault();
    setIsDrawing(false);
  };

  const clearCanvas = () => {
    const canvas = canvasRef.current;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    setIsEmpty(true);
    onSignatureChange(null);
  };

  const updateSignature = () => {
    if (mode === 'draw') {
      const canvas = canvasRef.current;
      const dataURL = canvas.toDataURL('image/png');
      onSignatureChange({
        type: 'draw',
        data: dataURL,
        metadata: {
          timestamp: new Date().toISOString(),
          mode: 'canvas'
        }
      });
    } else {
      if (typedSignature.trim()) {
        onSignatureChange({
          type: 'type',
          data: typedSignature,
          font: selectedFont,
          metadata: {
            timestamp: new Date().toISOString(),
            mode: 'typed',
            font: selectedFont
          }
        });
      } else {
        onSignatureChange(null);
      }
    }
  };

  const handleTypedSignatureChange = (e) => {
    setTypedSignature(e.target.value);
    setIsEmpty(!e.target.value.trim());
  };

  useEffect(() => {
    updateSignature();
  }, [typedSignature, selectedFont]);

  const handleModeChange = (newMode) => {
    setMode(newMode);
    if (newMode === 'draw') {
      setTypedSignature('');
    } else {
      clearCanvas();
    }
    setIsEmpty(true);
    onSignatureChange(null);
  };

  return (
    <div>
      <ModeSelector>
        <ModeButton 
          active={mode === 'draw'} 
          onClick={() => handleModeChange('draw')}
          type="button"
        >
          ✏️ Desenhar Assinatura
        </ModeButton>
        <ModeButton 
          active={mode === 'type'} 
          onClick={() => handleModeChange('type')}
          type="button"
        >
          ⌨️ Digitar Assinatura
        </ModeButton>
      </ModeSelector>

      {mode === 'draw' ? (
        <SignaturePadContainer>
          <Canvas
            ref={canvasRef}
            onMouseDown={startDrawing}
            onMouseMove={draw}
            onMouseUp={stopDrawing}
            onMouseLeave={stopDrawing}
            onTouchStart={startDrawing}
            onTouchMove={draw}
            onTouchEnd={stopDrawing}
          />
          <ControlsContainer>
            <span style={{ fontSize: '14px', color: '#6b7280' }}>
              Desenhe sua assinatura acima
            </span>
            <Button
              type="button"
              size="sm"
              $variant="outline"
              onClick={clearCanvas}
            >
              Limpar
            </Button>
          </ControlsContainer>
        </SignaturePadContainer>
      ) : (
        <TypedSignatureContainer>
          <TypedSignatureInput
            type="text"
            placeholder="Digite seu nome para criar a assinatura"
            value={typedSignature}
            onChange={handleTypedSignatureChange}
            className={`font-${selectedFont}`}
          />
          <FontSelector
            value={selectedFont}
            onChange={(e) => setSelectedFont(e.target.value)}
          >
            <option value="cursive">Fonte Cursiva</option>
            <option value="elegant">Fonte Elegante</option>
            <option value="modern">Fonte Moderna</option>
          </FontSelector>
        </TypedSignatureContainer>
      )}

      {isEmpty && (
        <p style={{ fontSize: '12px', color: '#dc2626', marginTop: '8px' }}>
          Por favor, {mode === 'draw' ? 'desenhe' : 'digite'} sua assinatura
        </p>
      )}
    </div>
  );
};

export default SignaturePad;