/**
 * OAuth 2.1 + PKCE scaffolding for WordPress.com / custom IdPs.
 *
 * Full production OAuth for public desktop clients needs a confidential proxy or
 * native WordPress.com app registration. This module provides:
 * - PKCE verifier/challenge helpers
 * - Authorize URL builder
 * - Token refresh skeleton
 *
 * Do NOT embed client secrets in MCP client JSON configs.
 */

import { createHash, randomBytes } from 'node:crypto';

export interface PkcePair {
  codeVerifier: string;
  codeChallenge: string;
  method: 'S256';
}

export function generatePkce(): PkcePair {
  const codeVerifier = randomBytes(32).toString('base64url');
  const codeChallenge = createHash('sha256').update(codeVerifier).digest('base64url');
  return { codeVerifier, codeChallenge, method: 'S256' };
}

export function buildAuthorizeUrl(params: {
  authorizeUrl: string;
  clientId: string;
  redirectUri: string;
  scope?: string;
  state?: string;
  codeChallenge: string;
}): string {
  const url = new URL(params.authorizeUrl);
  url.searchParams.set('response_type', 'code');
  url.searchParams.set('client_id', params.clientId);
  url.searchParams.set('redirect_uri', params.redirectUri);
  url.searchParams.set('code_challenge', params.codeChallenge);
  url.searchParams.set('code_challenge_method', 'S256');
  if (params.scope) url.searchParams.set('scope', params.scope);
  if (params.state) url.searchParams.set('state', params.state);
  return url.toString();
}

export interface TokenResponse {
  access_token: string;
  refresh_token?: string;
  expires_in?: number;
  token_type?: string;
}

/**
 * Exchange authorization code for tokens (confidential client / proxy side).
 */
export async function exchangeCode(params: {
  tokenUrl: string;
  clientId: string;
  clientSecret?: string;
  code: string;
  redirectUri: string;
  codeVerifier: string;
}): Promise<TokenResponse> {
  const body = new URLSearchParams({
    grant_type: 'authorization_code',
    client_id: params.clientId,
    code: params.code,
    redirect_uri: params.redirectUri,
    code_verifier: params.codeVerifier,
  });
  if (params.clientSecret) body.set('client_secret', params.clientSecret);

  const res = await fetch(params.tokenUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json' },
    body,
  });
  if (!res.ok) {
    throw new Error(`OAuth token exchange failed: HTTP ${res.status}`);
  }
  return (await res.json()) as TokenResponse;
}

/**
 * Refresh access token skeleton.
 */
export async function refreshAccessToken(params: {
  tokenUrl: string;
  clientId: string;
  clientSecret?: string;
  refreshToken: string;
}): Promise<TokenResponse> {
  const body = new URLSearchParams({
    grant_type: 'refresh_token',
    client_id: params.clientId,
    refresh_token: params.refreshToken,
  });
  if (params.clientSecret) body.set('client_secret', params.clientSecret);

  const res = await fetch(params.tokenUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json' },
    body,
  });
  if (!res.ok) {
    throw new Error(`OAuth refresh failed: HTTP ${res.status}`);
  }
  return (await res.json()) as TokenResponse;
}

export function bearerHeader(accessToken: string): string {
  return `Bearer ${accessToken}`;
}
