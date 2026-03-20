const messages = {
  nav: {
    home: 'Home',
    dashboard: 'Dashboard',
    documents: 'Documents',
    trash: 'Trash',
    admin: 'Admin',
    developer: 'Developer',
    notifications: 'Notifications',
    markAllRead: 'Mark all read',
    login: 'Login',
    register: 'Register',
    logout: 'Logout'
  },
  auth: {
    loginTitle: 'Login',
    registerTitle: 'Register',
    email: 'Email',
    password: 'Password',
    name: 'Name',
    signIn: 'Sign in',
    signUp: 'Create account',
    continue: 'Continue to your documents and activity timeline.',
    create: 'Create an account and start working in your secure DocBox workspace.'
  },
  dashboard: {
    title: 'Dashboard',
    subtitle: 'Manage documents, sharing, and activity from one place.',
    totalDocs: 'Total documents',
    ownedDocs: 'Owned by you',
    sharedDocs: 'Shared with you',
    folders: 'Folders',
    inTrash: 'In trash',
    recentDocs: 'Recent documents',
    recentActivity: 'Recent activity',
    openDocuments: 'Open documents',
    refresh: 'Refresh',
    viewAll: 'View all'
  },
  documents: {
    title: 'Documents',
    subtitle: 'Upload, organize, and open files quickly.',
    upload: 'Upload',
    folders: 'Folders',
    library: 'Library',
    search: 'Search by title or description',
    archive: 'Archive',
    all: 'All',
    active: 'Active',
    archived: 'Archived',
    apply: 'Apply',
    reset: 'Reset',
    refresh: 'Refresh',
    open: 'Open',
    trash: 'Trash',
    add: 'Add',
    newFolder: 'New folder',
    renameFolder: 'Rename folder',
    duplicateFolder: 'Duplicate folder',
    titleLabel: 'Title',
    description: 'Description',
    folder: 'Folder',
    fileLabel: 'Document file',
    supported: 'Supported: PDF, DOCX, XLSX, TXT, MD, JSON',
    uploadDocument: 'Upload document',
    duplicate: 'Duplicate',
    copyToFolder: 'Copy to folder',
    moveToFolder: 'Move to folder',
    quickPreview: 'Quick preview',
    savedViews: 'Saved views',
    saveCurrentView: 'Save current view',
    smartViews: 'Smart views',
    viewAll: 'All',
    viewOwned: 'Owned',
    viewShared: 'Shared',
    viewStarred: 'Starred',
    viewRecent: 'Recent',
    viewLarge: 'Large',
    viewName: 'View name',
    saveView: 'Save view',
    applyView: 'Apply view',
    deleteView: 'Delete view',
    star: 'Star',
    unstar: 'Unstar',
    bulk: {
      selected: 'Selected',
      archive: 'Archive',
      unarchive: 'Unarchive',
      star: 'Star',
      unstar: 'Unstar',
      delete: 'Delete'
    }
  },
  details: {
    download: 'Download',
    share: 'Share',
    delete: 'Delete',
    preview: 'Preview',
    saveMeta: 'Save metadata',
    uploadVersion: 'Upload new version',
    createVersion: 'Create version',
    inlineEditor: 'Inline editor',
    saveAsVersion: 'Save as new version',
    reload: 'Reload',
    owner: 'Owner',
    currentVersion: 'Current version',
    versionHistory: 'Version history',
    activity: 'Activity',
    restore: 'Restore',
    comments: 'Comments',
    addComment: 'Add comment',
    writeComment: 'Write a comment',
    postComment: 'Post comment'
  },
  admin: {
    title: 'Admin panel',
    subtitle: 'Manage users, roles, storage, and trash.',
    users: 'Users',
    admins: 'Admins',
    developers: 'Developers',
    docs: 'Documents',
    trash: 'Trash',
    usersRoles: 'Users and roles',
    globalTrash: 'Global trash',
    searchUsers: 'Search by name or email',
    searchTrash: 'Search deleted documents',
    restore: 'Restore',
    purge: 'Purge'
  },
  trash: {
    title: 'Trash',
    subtitle: 'Restore documents or delete them permanently.',
    deletedDocs: 'Deleted documents',
    refresh: 'Refresh',
    restore: 'Restore',
    deleteForever: 'Delete forever'
  },
  developer: {
    title: 'Developer panel',
    subtitle: 'Runtime, storage, and database diagnostics.',
    application: 'Application',
    storageDb: 'Storage and DB'
  },
  common: {
    general: 'General',
    noDescription: 'No description',
    yes: 'Yes',
    no: 'No',
    confirmDelete: 'Delete permanently?',
    movedToTrash: 'Document moved to trash'
  }
}

const resolveKey = (dictionary, key) =>
  key.split('.').reduce((acc, part) => (acc && acc[part] !== undefined ? acc[part] : null), dictionary)

export const t = (key) => {
  const value = resolveKey(messages, key)
  return value !== null && value !== undefined ? value : key
}

export const useI18n = () => ({ t })

export default {
  install(app) {
    app.provide('docboxI18n', { t })
    app.config.globalProperties.$t = t
  }
}
