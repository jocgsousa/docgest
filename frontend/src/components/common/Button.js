import styled, { css } from 'styled-components';

const Button = styled.button`
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-weight: 500;
  border-radius: 0.5rem;
  transition: all 0.15s ease-in-out;
  cursor: pointer;
  border: none;
  outline: none;
  text-decoration: none;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  
  &:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
  
  &:focus-visible {
    outline: 2px solid ${props => props.theme.colors.primary};
    outline-offset: 2px;
  }

  ${props => {
    switch (props.size) {
      case 'sm':
        return css`
          padding: 0.5rem 1rem;
          font-size: 0.875rem;
          line-height: 1.25rem;
        `;
      case 'lg':
        return css`
          padding: 0.75rem 1.5rem;
          font-size: 1.125rem;
          line-height: 1.75rem;
        `;
      default:
        return css`
          padding: 0.625rem 1.25rem;
          font-size: 1rem;
          line-height: 1.5rem;
        `;
    }
  }}

  ${props => {
    switch (props.$variant) {
      case 'primary':
        return css`
          background-color: ${props.theme.colors.primary};
          color: ${props.theme.colors.white};
          
          &:hover:not(:disabled) {
            background-color: ${props.theme.colors.primaryHover};
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transform: translateY(-1px);
          }
          
          &:active:not(:disabled) {
            transform: translateY(0);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
          }
        `;
      case 'secondary':
        return css`
          background-color: ${props.theme.colors.gray[100]};
          color: ${props.theme.colors.gray[700]};
          border: 1px solid ${props.theme.colors.gray[200]};
          
          &:hover:not(:disabled) {
            background-color: ${props.theme.colors.gray[50]};
            border-color: ${props.theme.colors.gray[300]};
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.06);
          }
        `;
      case 'success':
        return css`
          background-color: ${props.theme.colors.success};
          color: ${props.theme.colors.white};
          
          &:hover:not(:disabled) {
            background-color: #059669;
          }
        `;
      case 'danger':
        return css`
          background-color: ${props.theme.colors.danger};
          color: ${props.theme.colors.white};
          
          &:hover:not(:disabled) {
            background-color: #dc2626;
          }
        `;
      case 'outline':
        return css`
          background-color: transparent;
          color: ${props.theme.colors.primary};
          border: 1px solid ${props.theme.colors.gray[300]};
          
          &:hover:not(:disabled) {
            background-color: ${props.theme.colors.gray[50]};
            border-color: ${props.theme.colors.gray[400]};
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
          }
        `;
      case 'ghost':
        return css`
          background-color: transparent;
          color: ${props.theme.colors.gray[600]};
          box-shadow: none;
          
          &:hover:not(:disabled) {
            background-color: ${props.theme.colors.gray[100]};
            color: ${props.theme.colors.gray[900]};
          }
        `;
      default:
        return css`
          background-color: ${props.theme.colors.primary};
          color: ${props.theme.colors.white};
          
          &:hover:not(:disabled) {
            background-color: ${props.theme.colors.primaryHover};
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transform: translateY(-1px);
          }
          
          &:active:not(:disabled) {
            transform: translateY(0);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
          }
        `;
    }
  }}

  ${props => props.$fullWidth && css`
    width: 100%;
  `}
`;

export default Button;