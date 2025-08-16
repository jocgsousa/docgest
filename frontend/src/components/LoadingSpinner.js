import React from 'react';
import styled, { keyframes } from 'styled-components';

const spin = keyframes`
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
`;

const SpinnerContainer = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  padding: ${props => props.size === 'small' ? '8px' : props.size === 'large' ? '32px' : '16px'};
`;

const Spinner = styled.div`
  border: ${props => props.size === 'small' ? '2px' : props.size === 'large' ? '4px' : '3px'} solid ${props => props.theme.colors.gray[200]};
  border-top: ${props => props.size === 'small' ? '2px' : props.size === 'large' ? '4px' : '3px'} solid ${props => props.theme.colors.primary};
  border-radius: 50%;
  width: ${props => props.size === 'small' ? '16px' : props.size === 'large' ? '48px' : '24px'};
  height: ${props => props.size === 'small' ? '16px' : props.size === 'large' ? '48px' : '24px'};
  animation: ${spin} 1s linear infinite;
`;

const LoadingText = styled.div`
  margin-left: 12px;
  color: ${props => props.theme.colors.textSecondary};
  font-size: ${props => props.size === 'small' ? '12px' : props.size === 'large' ? '16px' : '14px'};
`;

function LoadingSpinner({ size = 'medium', text, className }) {
  return (
    <SpinnerContainer size={size} className={className}>
      <Spinner size={size} />
      {text && <LoadingText size={size}>{text}</LoadingText>}
    </SpinnerContainer>
  );
}

export default LoadingSpinner;