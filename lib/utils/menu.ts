export interface MenuItem {
  id: string;
  parentId: string | null;
  label: string;
  path: string;
  cssClasses: string[];
  children?: MenuItem[];
}

export function buildMenuTree(nodes: MenuItem[]): MenuItem[] {
  const tree: MenuItem[] = [];
  const childrenOf: Record<string, MenuItem[]> = {};

  // First pass: initialize children arrays
  nodes.forEach((node) => {
    childrenOf[node.id] = [];
    node.children = childrenOf[node.id];
  });

  // Second pass: build the tree
  nodes.forEach((node) => {
    if (node.parentId) {
      if (childrenOf[node.parentId]) {
        childrenOf[node.parentId].push(node);
      }
    } else {
      tree.push(node);
    }
  });

  return tree;
}
