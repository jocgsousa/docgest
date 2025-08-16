import React from 'react';
import styled from 'styled-components';

const StyledButton = styled.button`
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: ${props => {
    switch (props.size) {
      case 'small': return '6px 12px';
      case 'large': return '12px 24px';
      default: return '8px 16px';
    }
  }};
  font-size: ${props => {
    switch (props.size) {
      case 'small': return '14px';
      case 'large': return '16px';
      default: return '14px';
    }
  }};
  font-weight: 500;
  border-radius: 6px;
  border: 1px solid;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  min-height: ${props => {
    switch (props.size) {
      case 'small': return '32px';
      case 'large': return '44px';
      default: return '36px';
    }
  }};

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  ${props => {
    switch (props.variant) {
      case 'primary':
        return `
          background: #3182ce;
          border-color: #3182ce;
          color: white;
          
          &:hover:not(:disabled) {
            background: #2c5aa0;
            border-color: #2c5aa0;
          }
        `;
      case 'secondary':
        return `
          background: #e2e8f0;
          border-color: #e2e8f0;
          color: #4a5568;
          
          &:hover:not(:disabled) {
            background: #cbd5e0;
            border-color: #cbd5e0;
          }
        `;
      case 'success':
        return `
          background: #38a169;
          border-color: #38a169;
          color: white;
          
          &:hover:not(:disabled) {
            background: #2f855a;
            border-color: #2f855a;
          }
        `;
      case 'danger':
        return `
          background: #e53e3e;
          border-color: #e53e3e;
          color: white;
          
          &:hover:not(:disabled) {
            background: #c53030;
            border-color: #c53030;
          }
        `;
      case 'warning':
        return `
          background: #d69e2e;
          border-color: #d69e2e;
          color: white;
          
          &:hover:not(:disabled) {
            background: #b7791f;
            border-color: #b7791f;
          }
        `;
      case 'outline':
        return `
          background: transparent;
          border-color: #3182ce;
          color: #3182ce;
          
          &:hover:not(:disabled) {
            background: #3182ce;
            color: white;
          }
        `;
      case 'ghost':
        return `
          background: transparent;
          border-color: transparent;
          color: #3182ce;
          
          &:hover:not(:disabled) {
            background: #ebf8ff;
          }
        `;
      default:
        return `
          background: #f7fafc;
          border-color: #e2e8f0;
          color: #4a5568;
          
          &:hover:not(:disabled) {
            background: #edf2f7;
            border-color: #cbd5e0;
          }
        `;
    }
  }}
`;

const Button = ({ 
  children, 
  variant = 'default', 
  size = 'medium', 
  disabled = false,
  loading = false,
  icon,
  onClick,
  type = 'button',
  className,
  ...props 
}) => {
  return (
    <StyledButton
      variant={variant}
      size={size}
      disabled={disabled || loading}
      onClick={onClick}
      type={type}
      className={className}
      {...props}
    >
      {loading && <i className="fas fa-spinner fa-spin" />}
      {!loading && icon && <i className={icon} />}
      {children}
    </StyledButton>
  );
};

export default Button;