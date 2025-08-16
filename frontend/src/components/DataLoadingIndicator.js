import React from 'react';
import styled from 'styled-components';
import { useData } from '../contexts/DataContext';
import LoadingSpinner from './LoadingSpinner';

const IndicatorContainer = styled.div`
  position: fixed;
  top: 20px;
  right: 20px;
  background: ${props => props.theme.colors.white};
  border: 1px solid ${props => props.theme.colors.border};
  border-radius: 8px;
  padding: 12px 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
  
  ${props => !props.show && `
    opacity: 0;
    transform: translateY(-10px);
    pointer-events: none;
  `}
`;

const LoadingText = styled.div`
  font-size: 14px;
  color: ${props => props.theme.colors.textSecondary};
  font-weight: 500;
`;

function DataLoadingIndicator() {
  const { loading } = useData();
  
  const loadingItems = [];
  
  if (loading.companies) loadingItems.push('empresas');
  if (loading.plans) loadingItems.push('planos');
  if (loading.branches) loadingItems.push('filiais');
  if (loading.users) loadingItems.push('usuários');
  
  const isLoading = loadingItems.length > 0;
  
  if (!isLoading) return null;
  
  const loadingText = loadingItems.length === 1 
    ? `Carregando ${loadingItems[0]}...`
    : `Carregando dados (${loadingItems.length} itens)...`;
  
  return (
    <IndicatorContainer show={isLoading}>
      <LoadingSpinner size="small" />
      <LoadingText>{loadingText}</LoadingText>
    </IndicatorContainer>
  );
}

export default DataLoadingIndicator;