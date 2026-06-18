import { TransparenciaTree, FileNode } from '../../components/TransparenciaTree';
import { Metadata } from 'next';

export const dynamic = 'force-dynamic';
export const revalidate = 0;

const BUCKET_NAME = process.env.NEXT_PUBLIC_GCP_BUCKET_NAME || 'my-bucket-name';
const BUCKET_HOST = process.env.NEXT_PUBLIC_GCP_BUCKET_HOST || `https://storage.googleapis.com/${BUCKET_NAME}`;

export const metadata: Metadata = {
  title: 'Transparencia Ley 5189/2014',
  description: 'Documentos de acceso público, resoluciones, balances y archivos institucionales para libre descarga.',
};

interface GCPObject {
  name: string;
  size: string;
  mediaLink: string;
}

function buildTreeFromGCPObjects(items: GCPObject[], prefix: string): FileNode[] {
  const rootNodes: FileNode[] = [];
  const map = new Map<string, FileNode>();

  const getOrCreateDir = (dirPath: string): FileNode => {
    if (map.has(dirPath)) return map.get(dirPath)!;
    
    const parts = dirPath.split('/');
    const name = parts[parts.length - 1];
    
    const node: FileNode = {
      name,
      type: 'directory',
      path: dirPath,
      children: []
    };
    
    map.set(dirPath, node);
    
    if (parts.length === 1) {
      rootNodes.push(node);
    } else {
      const parentPath = parts.slice(0, -1).join('/');
      const parentNode = getOrCreateDir(parentPath);
      parentNode.children!.push(node);
    }
    
    return node;
  };

  items.forEach(item => {
    let relPath = item.name.substring(prefix.length);
    if (!relPath) return; 
    
    const parts = relPath.split('/');
    if (parts.some(p => p.startsWith('.'))) return;
    
    const isFolderObject = relPath.endsWith('/');
    if (isFolderObject) {
      relPath = relPath.slice(0, -1);
      getOrCreateDir(relPath);
      return;
    }

    const fileName = parts.pop()!;
    const dirPath = parts.join('/');
    
    const encodedPath = relPath.split('/').map(encodeURIComponent).join('/');
    // Base URL is the bucket public URL
    const publicUrl = `${BUCKET_HOST}/transparencia/${encodedPath}`;

    const fileNode: FileNode = {
      name: fileName,
      type: 'file',
      path: relPath,
      url: publicUrl,
      size: parseInt(item.size, 10) || 0,
      extension: `.${fileName.split('.').pop()}`
    };

    if (dirPath === '') {
      rootNodes.push(fileNode);
    } else {
      const parentNode = getOrCreateDir(dirPath);
      parentNode.children!.push(fileNode);
    }
  });

  const sortNodes = (nodes: FileNode[]) => {
    nodes.sort((a, b) => {
      if (a.type === 'directory' && b.type === 'file') return -1;
      if (a.type === 'file' && b.type === 'directory') return 1;
      return a.name.localeCompare(b.name);
    });
    nodes.forEach(node => {
      if (node.children) sortNodes(node.children);
    });
  };
  
  sortNodes(rootNodes);
  return rootNodes;
}

async function getTransparenciaFiles(): Promise<FileNode[]> {
  const bucketName = BUCKET_NAME;
  const prefix = 'transparencia/';
  let url = `https://storage.googleapis.com/storage/v1/b/${bucketName}/o?prefix=${prefix}&maxResults=1000`;
  
  const allItems: GCPObject[] = [];
  
  try {
    let hasNextPage = true;
    while (hasNextPage) {
      const response = await fetch(url, { cache: 'no-store' });
      if (!response.ok) {
        console.error('Error fetching from GCP:', await response.text());
        break;
      }
      const data = await response.json();
      if (data.items) {
        allItems.push(...data.items);
      }
      
      if (data.nextPageToken) {
        // Construct the URL for the next page
        url = `https://storage.googleapis.com/storage/v1/b/${bucketName}/o?prefix=${prefix}&maxResults=1000&pageToken=${data.nextPageToken}`;
      } else {
        hasNextPage = false;
      }
    }
    
    return buildTreeFromGCPObjects(allItems, prefix);
  } catch (error) {
    console.error('Error fetching GCP bucket:', error);
    return [];
  }
}

export default async function TransparenciaPage() {
  const tree = await getTransparenciaFiles();

  return (
    <main className="section container" style={{ padding: '4rem 2rem' }}>
      <div className="fade-in visible">
        <h1 className="section-title">Transparencia Ley 5189/2014</h1>
        <p className="section-subtitle">
          Documentos de acceso público
        </p>
      </div>

      <div className="content fade-in visible" style={{ animationDelay: '0.2s', marginTop: '2rem' }}>
        <p>
          En este portal ponemos a disposición de la ciudadanía los documentos institucionales,
          resoluciones y demás archivos de carácter público para su libre acceso y descarga.
        </p>

        {tree.length > 0 ? (
          <TransparenciaTree nodes={tree} />
        ) : (
          <div style={{ marginTop: '2rem' }}>
            <TransparenciaTree nodes={[]} />
          </div>
        )}
      </div>
    </main>
  );
}
