import React, { useEffect } from 'react';
import styled, { css } from 'styled-components';
import { createPortal } from 'react-dom';

const Overlay = styled.div`
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
`;

const ModalContainer = styled.div`
  background-color: ${props => props.theme.colors.white};
  border-radius: 0.5rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  max-height: 90vh;
  overflow-y: auto;
  width: 100%;
  
  ${props => {
    switch (props.size) {
      case 'sm':
        return css`max-width: 24rem;`;
      case 'lg':
        return css`max-width: 48rem;`;
      case 'xl':
        return css`max-width: 64rem;`;
      case 'full':
        return css`
          max-width: 95vw;
          max-height: 95vh;
        `;
      default:
        return css`max-width: 32rem;`;
    }
  }}
`;

const ModalHeader = styled.div`
  padding: 1.5rem 1.5rem 0 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid ${props => props.theme.colors.gray[200]};
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
`;

const ModalTitle = styled.h2`
  font-size: 1.25rem;
  font-weight: 600;
  color: ${props => props.theme.colors.gray[900]};
  margin: 0;
`;

const CloseButton = styled.button`
  background: none;
  border: none;
  font-size: 1.5rem;
  color: ${props => props.theme.colors.gray[400]};
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 0.25rem;
  transition: all 0.2s ease-in-out;
  
  &:hover {
    color: ${props => props.theme.colors.gray[600]};
    background-color: ${props => props.theme.colors.gray[100]};
  }
`;

const ModalBody = styled.div`
  padding: 0 1.5rem;
`;

const ModalFooter = styled.div`
  padding: 1rem 1.5rem 1.5rem 1.5rem;
  border-top: 1px solid ${props => props.theme.colors.gray[200]};
  margin-top: 1.5rem;
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  
  ${props => props.justify === 'start' && css`
    justify-content: flex-start;
  `}
  
  ${props => props.justify === 'center' && css`
    justify-content: center;
  `}
  
  ${props => props.justify === 'between' && css`
    justify-content: space-between;
  `}
`;

function Modal({ 
  isOpen, 
  onClose, 
  title, 
  children, 
  size = 'md',
  closeOnOverlayClick = false,
  showCloseButton = true,
  footer,
  footerJustify = 'end'
}) {
  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = 'unset';
    }
    
    return () => {
      document.body.style.overflow = 'unset';
    };
  }, [isOpen]);
  
  if (!isOpen) return null;
  
  const handleOverlayClick = (e) => {
    // Modal só fecha pelo botão de fechar
    e.stopPropagation();
  };
  
  return createPortal(
    <Overlay onClick={handleOverlayClick}>
      <ModalContainer size={size} onClick={(e) => e.stopPropagation()}>
        {(title || showCloseButton) && (
          <ModalHeader>
            {title && <ModalTitle>{title}</ModalTitle>}
            {showCloseButton && (
              <CloseButton onClick={onClose}>
                ×
              </CloseButton>
            )}
          </ModalHeader>
        )}
        
        <ModalBody>
          {children}
        </ModalBody>
        
        {footer && (
          <ModalFooter justify={footerJustify}>
            {footer}
          </ModalFooter>
        )}
      </ModalContainer>
    </Overlay>,
    document.body
  );
}

Modal.Header = ModalHeader;
Modal.Title = ModalTitle;
Modal.Body = ModalBody;
Modal.Footer = ModalFooter;

export default Modal;