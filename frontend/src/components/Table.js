import React from 'react';
import styled from 'styled-components';

const TableContainer = styled.div`
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
`;

const StyledTable = styled.table`
  width: 100%;
  border-collapse: collapse;
`;

const TableHeader = styled.thead`
  background: #f9fafb;
`;

const TableHeaderRow = styled.tr`
  border-bottom: 1px solid #e5e7eb;
`;

const TableHeaderCell = styled.th`
  padding: 12px 16px;
  text-align: left;
  font-weight: 600;
  font-size: 14px;
  color: #374151;
  border-right: 1px solid #e5e7eb;
  
  &:last-child {
    border-right: none;
  }
  
  ${props => props.sortable && `
    cursor: pointer;
    user-select: none;
    transition: background-color 0.2s ease;
    
    &:hover {
      background: #f3f4f6;
    }
    
    &:after {
      content: '';
      display: inline-block;
      margin-left: 8px;
      width: 0;
      height: 0;
      border-left: 4px solid transparent;
      border-right: 4px solid transparent;
      border-bottom: 4px solid #9ca3af;
      opacity: 0.5;
    }
    
    &.sort-asc:after {
      border-bottom: 4px solid #374151;
      border-top: none;
      opacity: 1;
    }
    
    &.sort-desc:after {
      border-top: 4px solid #374151;
      border-bottom: none;
      opacity: 1;
    }
  `}
`;

const TableBody = styled.tbody``;

const TableRow = styled.tr`
  border-bottom: 1px solid #f3f4f6;
  transition: background-color 0.2s ease;
  
  &:hover {
    background: #f9fafb;
  }
  
  &:last-child {
    border-bottom: none;
  }
`;

const TableCell = styled.td`
  padding: 12px 16px;
  font-size: 14px;
  color: #374151;
  border-right: 1px solid #f3f4f6;
  vertical-align: middle;
  
  &:last-child {
    border-right: none;
  }
`;

const EmptyState = styled.div`
  padding: 48px 24px;
  text-align: center;
  color: #6b7280;
`;

const EmptyIcon = styled.div`
  font-size: 48px;
  color: #d1d5db;
  margin-bottom: 16px;
`;

const EmptyTitle = styled.h3`
  font-size: 18px;
  font-weight: 500;
  color: #374151;
  margin-bottom: 8px;
`;

const EmptyDescription = styled.p`
  font-size: 14px;
  color: #6b7280;
  margin: 0;
`;

const LoadingState = styled.div`
  padding: 48px 24px;
  text-align: center;
  color: #6b7280;
`;

const LoadingSpinner = styled.div`
  display: inline-block;
  width: 32px;
  height: 32px;
  border: 3px solid #f3f4f6;
  border-radius: 50%;
  border-top-color: #3b82f6;
  animation: spin 1s ease-in-out infinite;
  margin-bottom: 16px;
  
  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }
`;

const StatusBadge = styled.span`
  display: inline-flex;
  align-items: center;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  
  ${props => {
    switch (props.status) {
      case 'active':
      case 'ativo':
      case 'assinado':
        return `
          background: #dcfce7;
          color: #166534;
        `;
      case 'inactive':
      case 'inativo':
      case 'cancelado':
        return `
          background: #fee2e2;
          color: #991b1b;
        `;
      case 'pending':
      case 'pendente':
        return `
          background: #fef3c7;
          color: #92400e;
        `;
      case 'rascunho':
        return `
          background: #f3f4f6;
          color: #374151;
        `;
      case 'enviado':
        return `
          background: #dbeafe;
          color: #1e40af;
        `;
      default:
        return `
          background: #f3f4f6;
          color: #374151;
        `;
    }
  }}
`;

const Table = ({
  columns = [],
  data = [],
  loading = false,
  emptyTitle = 'Nenhum item encontrado',
  emptyDescription = 'Não há dados para exibir no momento.',
  emptyIcon = 'fas fa-inbox',
  onSort,
  sortColumn,
  sortDirection,
  className,
  ...props
}) => {
  const handleSort = (column) => {
    if (column.sortable && onSort) {
      onSort(column.key);
    }
  };

  const renderCellContent = (item, column) => {
    if (column.render) {
      return column.render(item[column.key], item);
    }
    
    if (column.type === 'status') {
      return <StatusBadge status={item[column.key]}>{item[column.key]}</StatusBadge>;
    }
    
    if (column.type === 'date') {
      const date = new Date(item[column.key]);
      return date.toLocaleDateString('pt-BR');
    }
    
    if (column.type === 'datetime') {
      const date = new Date(item[column.key]);
      return date.toLocaleString('pt-BR');
    }
    
    if (column.type === 'currency') {
      return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
      }).format(item[column.key]);
    }
    
    return item[column.key];
  };

  const getSortClass = (column) => {
    if (sortColumn === column.key) {
      return sortDirection === 'asc' ? 'sort-asc' : 'sort-desc';
    }
    return '';
  };

  return (
    <TableContainer className={className} {...props}>
      {loading ? (
        <LoadingState>
          <LoadingSpinner />
          <p>Carregando dados...</p>
        </LoadingState>
      ) : data.length === 0 ? (
        <EmptyState>
          <EmptyIcon>
            <i className={emptyIcon} />
          </EmptyIcon>
          <EmptyTitle>{emptyTitle}</EmptyTitle>
          <EmptyDescription>{emptyDescription}</EmptyDescription>
        </EmptyState>
      ) : (
        <StyledTable>
          <TableHeader>
            <TableHeaderRow>
              {columns.map((column) => (
                <TableHeaderCell
                  key={column.key}
                  sortable={column.sortable}
                  className={getSortClass(column)}
                  onClick={() => handleSort(column)}
                  style={{ width: column.width }}
                >
                  {column.title}
                </TableHeaderCell>
              ))}
            </TableHeaderRow>
          </TableHeader>
          <TableBody>
            {data.map((item, index) => (
              <TableRow key={item.id || index}>
                {columns.map((column) => (
                  <TableCell key={column.key}>
                    {renderCellContent(item, column)}
                  </TableCell>
                ))}
              </TableRow>
            ))}
          </TableBody>
        </StyledTable>
      )}
    </TableContainer>
  );
};

export default Table;