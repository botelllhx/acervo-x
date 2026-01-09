import React from 'react';
import { cn } from '../../lib/utils';

export function Select({ className, children, ...props }) {
  return (
    <select
      className={cn('acervox-select', className)}
      {...props}
    >
      {children}
    </select>
  );
}
