import styled, { css } from 'styled-components';

const InputContainer = styled.div`
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
`;

const Label = styled.label`
  font-weight: 500;
  font-size: 0.875rem;
  color: ${props => props.theme.colors.gray[700]};
`;

const InputWrapper = styled.div`
  position: relative;
  display: flex;
  align-items: center;
`;

const StyledInput = styled.input`
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid ${props => props.theme.colors.gray[300]};
  border-radius: 0.375rem;
  font-size: 1rem;
  line-height: 1.5;
  background-color: ${props => props.theme.colors.white};
  color: ${props => props.theme.colors.gray[900]};
  transition: all 0.2s ease-in-out;
  
  &:focus {
    outline: none;
    border-color: ${props => props.theme.colors.primary};
    box-shadow: 0 0 0 3px ${props => props.theme.colors.primary}20;
  }
  
  &:disabled {
    background-color: ${props => props.theme.colors.gray[100]};
    color: ${props => props.theme.colors.gray[500]};
    cursor: not-allowed;
  }
  
  &::placeholder {
    color: ${props => props.theme.colors.gray[400]};
  }
  
  ${props => props.error && css`
    border-color: ${props.theme.colors.danger};
    
    &:focus {
      border-color: ${props.theme.colors.danger};
      box-shadow: 0 0 0 3px ${props.theme.colors.danger}20;
    }
  `}
  
  ${props => props.$hasIcon && css`
    padding-left: 2.75rem;
  `}
  
  ${props => props.$hasRightIcon && css`
    padding-right: 2.75rem;
  `}
`;

const IconContainer = styled.div`
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: ${props => props.theme.colors.gray[400]};
  pointer-events: none;
`;

const RightIconContainer = styled.div`
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: ${props => props.theme.colors.gray[400]};
  cursor: pointer;
`;

const ErrorMessage = styled.span`
  font-size: 0.875rem;
  color: ${props => props.theme.colors.danger};
  margin-top: 0.25rem;
`;

const HelperText = styled.span`
  font-size: 0.875rem;
  color: ${props => props.theme.colors.gray[500]};
  margin-top: 0.25rem;
`;

function Input({ 
  label, 
  error, 
  helperText, 
  icon, 
  rightIcon, 
  onRightIconClick,
  className,
  ...props 
}) {
  return (
    <InputContainer className={className}>
      {label && <Label>{label}</Label>}
      <InputWrapper>
        {icon && <IconContainer>{icon}</IconContainer>}
        <StyledInput 
          error={error}
          $hasIcon={!!icon}
          $hasRightIcon={!!rightIcon}
          {...props} 
        />
        {rightIcon && (
          <RightIconContainer onClick={onRightIconClick}>
            {rightIcon}
          </RightIconContainer>
        )}
      </InputWrapper>
      {error && <ErrorMessage>{error}</ErrorMessage>}
      {helperText && !error && <HelperText>{helperText}</HelperText>}
    </InputContainer>
  );
}

export default Input;