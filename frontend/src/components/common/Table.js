import styled, { css } from 'styled-components';

const TableContainer = styled.div`
  overflow-x: auto;
  border-radius: 0.5rem;
  border: 1px solid ${props => props.theme.colors.gray[200]};
`;

const StyledTable = styled.table`
  width: 100%;
  border-collapse: collapse;
  background-color: ${props => props.theme.colors.white};
`;

const TableHead = styled.thead`
  background-color: ${props => props.theme.colors.gray[50]};
`;

const TableBody = styled.tbody`
  background-color: ${props => props.theme.colors.white};
`;

const TableRow = styled.tr`
  border-bottom: 1px solid ${props => props.theme.colors.gray[200]};
  
  &:last-child {
    border-bottom: none;
  }
  
  ${props => props.clickable && css`
    cursor: pointer;
    transition: background-color 0.2s ease-in-out;
    
    &:hover {
      background-color: ${props.theme.colors.gray[50]};
    }
  `}
  
  ${props => props.selected && css`
    background-color: ${props.theme.colors.primary}10;
  `}
`;

const TableHeader = styled.th`
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 600;
  font-size: 0.875rem;
  color: ${props => props.theme.colors.gray[700]};
  text-transform: uppercase;
  letter-spacing: 0.05em;
  
  ${props => props.align === 'center' && css`
    text-align: center;
  `}
  
  ${props => props.align === 'right' && css`
    text-align: right;
  `}
  
  ${props => props.sortable && css`
    cursor: pointer;
    user-select: none;
    
    &:hover {
      background-color: ${props.theme.colors.gray[100]};
    }
  `}
`;

const TableCell = styled.td`
  padding: 1rem;
  font-size: 0.875rem;
  color: ${props => props.theme.colors.gray[900]};
  
  ${props => props.align === 'center' && css`
    text-align: center;
  `}
  
  ${props => props.align === 'right' && css`
    text-align: right;
  `}
`;

const EmptyState = styled.div`
  padding: 3rem 1rem;
  text-align: center;
  color: ${props => props.theme.colors.gray[500]};
  font-size: 0.875rem;
`;

const LoadingState = styled.div`
  padding: 3rem 1rem;
  text-align: center;
  color: ${props => props.theme.colors.gray[500]};
  font-size: 0.875rem;
`;

const SortIcon = styled.span`
  margin-left: 0.5rem;
  font-size: 0.75rem;
  color: ${props => props.theme.colors.gray[400]};
`;

function Table({ 
  columns = [], 
  data = [], 
  loading = false,
  emptyMessage = 'Nenhum dado encontrado',
  loadingMessage = 'Carregando...',
  onRowClick,
  selectedRows = [],
  sortBy,
  sortOrder,
  onSort,
  className
}) {
  const handleSort = (column) => {
    if (column.sortable && onSort) {
      const newOrder = sortBy === column.key && sortOrder === 'asc' ? 'desc' : 'asc';
      onSort(column.key, newOrder);
    }
  };
  
  const getSortIcon = (column) => {
    if (!column.sortable) return null;
    if (sortBy !== column.key) return <SortIcon>↕</SortIcon>;
    return <SortIcon>{sortOrder === 'asc' ? '↑' : '↓'}</SortIcon>;
  };
  
  if (loading) {
    return (
      <TableContainer className={className}>
        <LoadingState>{loadingMessage}</LoadingState>
      </TableContainer>
    );
  }
  
  if (data.length === 0) {
    return (
      <TableContainer className={className}>
        <EmptyState>{emptyMessage}</EmptyState>
      </TableContainer>
    );
  }
  
  return (
    <TableContainer className={className}>
      <StyledTable>
        <TableHead>
          <TableRow>
            {columns.map((column) => (
              <TableHeader
                key={column.key}
                align={column.align}
                sortable={column.sortable}
                onClick={() => handleSort(column)}
              >
                {column.title}
                {getSortIcon(column)}
              </TableHeader>
            ))}
          </TableRow>
        </TableHead>
        <TableBody>
          {data.map((row, index) => (
            <TableRow
              key={row.id || index}
              clickable={!!onRowClick}
              selected={selectedRows.includes(row.id)}
              onClick={() => onRowClick && onRowClick(row)}
            >
              {columns.map((column) => (
                <TableCell key={column.key} align={column.align}>
                  {column.render ? column.render(row[column.key], row) : row[column.key]}
                </TableCell>
              ))}
            </TableRow>
          ))}
        </TableBody>
      </StyledTable>
    </TableContainer>
  );
}

Table.Container = TableContainer;
Table.Table = StyledTable;
Table.Head = TableHead;
Table.Body = TableBody;
Table.Row = TableRow;
Table.Header = TableHeader;
Table.Cell = TableCell;

export default Table;