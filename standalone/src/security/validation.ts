import { z } from 'zod';

export const confirmSchema = z.object({
  confirm: z.literal(true),
});

export function requireConfirm(input: { confirm?: boolean }): void {
  if (input.confirm !== true) {
    throw new Error('This operation requires confirm: true');
  }
}

export function requireWrites(enabled: boolean): void {
  if (!enabled) {
    throw new Error('Write tools are disabled (MCP_ENABLE_WRITES=false)');
  }
}

export const idSchema = z.number().int().positive();

export const paginationSchema = z.object({
  page: z.number().int().min(1).default(1).optional(),
  per_page: z.number().int().min(1).max(100).default(10).optional(),
  search: z.string().max(200).optional(),
});
