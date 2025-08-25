import React, { useState, useEffect } from 'react';
import styled, { keyframes } from 'styled-components';
import { CheckCircle, AlertCircle, Info, X } from 'lucide-react';

const slideIn = keyframes`
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
`;

const slideOut = keyframes`
  from {
    transform: translateX(0);
    opacity: 1;
  }
  to {
    transform: translateX(100%);
    opacity: 0;
  }
`;

const ToastContainer = styled.div`
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 12px;
  pointer-events: none;
`;

const ToastItem = styled.div`
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  min-width: 320px;
  max-width: 480px;
  pointer-events: auto;
  animation: ${props => props.isExiting ? slideOut : slideIn} 0.3s ease-out;
  border: 1px solid;
  
  &.success {
    background-color: ${props => props.theme.colors.green || '#10B981'};
    border-color: ${props => props.theme.colors.green || '#10B981'};
    color: white;
  }
  
  &.error {
    background-color: ${props => props.theme.colors.red || '#EF4444'};
    border-color: ${props => props.theme.colors.red || '#EF4444'};
    color: white;
  }
  
  &.info {
    background-color: ${props => props.theme.colors.blue || '#3B82F6'};
    border-color: ${props => props.theme.colors.blue || '#3B82F6'};
    color: white;
  }
`;

const ToastIcon = styled.div`
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 24px;
`;

const ToastContent = styled.div`
  flex: 1;
`;

const ToastTitle = styled.div`
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 4px;
`;

const ToastMessage = styled.div`
  font-size: 13px;
  opacity: 0.9;
  line-height: 1.4;
`;

const CloseButton = styled.button`
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0.7;
  transition: opacity 0.2s;
  
  &:hover {
    opacity: 1;
    background-color: rgba(255, 255, 255, 0.1);
  }
`;

const ProgressBar = styled.div`
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  background-color: rgba(255, 255, 255, 0.3);
  border-radius: 0 0 8px 8px;
  transition: width linear;
  width: ${props => props.progress}%;
`;

const ToastItemWrapper = styled.div`
  position: relative;
`;

function Toast({ toasts, removeToast }) {
  const getIcon = (type) => {
    switch (type) {
      case 'success':
        return <CheckCircle size={20} />;
      case 'error':
        return <AlertCircle size={20} />;
      case 'info':
        return <Info size={20} />;
      default:
        return <Info size={20} />;
    }
  };

  return (
    <ToastContainer>
      {toasts.map((toast) => (
        <ToastItemWrapper key={toast.id}>
          <ToastItem 
            className={toast.type}
            isExiting={toast.isExiting}
          >
            <ToastIcon>
              {getIcon(toast.type)}
            </ToastIcon>
            <ToastContent>
              <ToastTitle>{toast.title}</ToastTitle>
              <ToastMessage>{toast.message}</ToastMessage>
            </ToastContent>
            <CloseButton onClick={() => removeToast(toast.id)}>
              <X size={16} />
            </CloseButton>
            {toast.showProgress && (
              <ProgressBar progress={toast.progress} />
            )}
          </ToastItem>
        </ToastItemWrapper>
      ))}
    </ToastContainer>
  );
}

export default Toast;