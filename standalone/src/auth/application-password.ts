/**
 * Application Password Basic auth header.
 * Spaces in the password are stripped (WordPress displays them for readability).
 */
export function applicationPasswordHeader(username: string, applicationPassword: string): string {
  const password = applicationPassword.replace(/\s+/g, '');
  const token = Buffer.from(`${username}:${password}`, 'utf8').toString('base64');
  return `Basic ${token}`;
}
