export interface WpPost {
  id: number;
  date_gmt?: string;
  modified_gmt?: string;
  slug?: string;
  status: string;
  type: string;
  link?: string;
  title: { rendered: string; raw?: string };
  content: { rendered: string; raw?: string };
  excerpt: { rendered: string; raw?: string };
  author: number;
  categories?: number[];
  tags?: number[];
  parent?: number;
}

export interface WpMedia {
  id: number;
  date_gmt?: string;
  slug?: string;
  title: { rendered: string };
  source_url?: string;
  mime_type?: string;
  alt_text?: string;
}

export interface WpTerm {
  id: number;
  name: string;
  slug: string;
  description?: string;
  parent?: number;
  count?: number;
}

export interface WpComment {
  id: number;
  post: number;
  parent: number;
  author_name: string;
  author_email?: string;
  date_gmt?: string;
  content: { rendered: string; raw?: string };
  status: string;
}

export interface WpUser {
  id: number;
  name: string;
  slug: string;
  roles?: string[];
}

export interface WpErrorBody {
  code?: string;
  message?: string;
  data?: { status?: number };
}
