'use client';

import React, { useState } from 'react';
import {
  Folder,
  FolderOpen,
  FileText,
  FileImage,
  File,
  Download,
  FileArchive
} from 'lucide-react';
import styles from './TransparenciaTree.module.css';

export type FileNode = {
  name: string;
  type: 'file' | 'directory';
  path: string;
  url?: string;
  size?: number;
  extension?: string;
  children?: FileNode[];
};

interface TransparenciaTreeProps {
  nodes: FileNode[];
}

function formatBytes(bytes: number, decimals = 2) {
  if (!+bytes) return '0 Bytes';
  const k = 1024;
  const dm = decimals < 0 ? 0 : decimals;
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))}${sizes[i]}`;
}

const TreeNode: React.FC<{ node: FileNode }> = ({ node }) => {
  const [isOpen, setIsOpen] = useState(false);

  const isDir = node.type === 'directory';

  const toggleOpen = () => {
    if (isDir) {
      setIsOpen(!isOpen);
    }
  };

  const getIcon = () => {
    if (isDir) {
      return isOpen ? (
        <FolderOpen className={`${styles.icon} ${styles.iconFolder}`} size={20} />
      ) : (
        <Folder className={`${styles.icon} ${styles.iconFolder}`} size={20} />
      );
    }

    const ext = node.extension?.toLowerCase();
    if (ext === '.pdf') {
      return <FileText className={`${styles.icon} ${styles.iconPdf}`} size={20} />
    } else if (['.png', '.jpg', '.jpeg', '.gif', '.webp'].includes(ext || '')) {
      return <FileImage className={`${styles.icon} ${styles.iconImage}`} size={20} />
    } else if (['.zip', '.rar', '.tar', '.gz'].includes(ext || '')) {
      return <FileArchive className={`${styles.icon} ${styles.iconFile}`} size={20} />
    }

    return <File className={`${styles.icon} ${styles.iconFile}`} size={20} />
  };

  const RowContent = (
    <>
      <div className={styles.nodeContent}>
        {getIcon()}
        <span className={styles.nodeName}>{node.name}</span>
      </div>

      {!isDir && (
        <>
          <span className={styles.nodeSize}>
            {node.size ? formatBytes(node.size) : ''}
          </span>
          <div className={styles.downloadBtn}>
            <Download size={16} />
            Descargar
          </div>
        </>
      )}
    </>
  );

  return (
    <li className={styles.nodeItem}>
      {isDir ? (
        <div className={styles.nodeRow} onClick={toggleOpen}>
          {RowContent}
        </div>
      ) : (
        <a
          href={node.url || `/api/transparencia/download?file=${encodeURIComponent(node.path)}`}
          target="_blank"
          rel="noopener noreferrer"
          className={styles.nodeRow}
          download={node.name}
        >
          {RowContent}
        </a>
      )}

      {isDir && isOpen && node.children && node.children.length > 0 && (
        <ul className={styles.nodeList}>
          {node.children.map((child) => (
            <TreeNode key={child.path} node={child} />
          ))}
        </ul>
      )}
    </li>
  );
};

export const TransparenciaTree: React.FC<TransparenciaTreeProps> = ({ nodes }) => {
  if (!nodes || nodes.length === 0) {
    return (
      <div className={styles.emptyState}>
        <FolderOpen size={48} className={styles.emptyIcon} />
        <div>
          <p>No se encontraron archivos de transparencia en este momento.</p>
          <p style={{ fontSize: '0.9rem' }}>Los documentos estarán disponibles pronto.</p>
        </div>
      </div>
    );
  }

  return (
    <div className={styles.treeContainer}>
      <ul className={styles.nodeList}>
        {nodes.map((node) => (
          <TreeNode key={node.path} node={node} />
        ))}
      </ul>
    </div>
  );
};
