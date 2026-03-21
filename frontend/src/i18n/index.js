const en = {
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
    postComment: 'Post comment',
    confirmMoveToTrashTitle: 'Move to trash?',
    confirmMoveToTrashMessage: 'The document will be moved to trash and can be restored later.'
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
    purge: 'Purge',
    confirmPurgeTitle: 'Delete permanently?',
    confirmPurgeMessage: 'This action cannot be undone.'
  },
  trash: {
    title: 'Trash',
    subtitle: 'Restore documents or delete them permanently.',
    deletedDocs: 'Deleted documents',
    refresh: 'Refresh',
    restore: 'Restore',
    deleteForever: 'Delete forever',
    search: 'Search in trash',
    apply: 'Apply',
    reset: 'Reset',
    selected: 'Selected',
    unknownOwner: 'Unknown owner',
    confirmPurgeTitle: 'Delete forever?',
    confirmPurgeMessage: 'The document will be permanently deleted.',
    confirmPurgeSelectedMessage: 'Selected documents will be permanently deleted.'
  },
  developer: {
    title: 'Developer panel',
    subtitle: 'Runtime, storage, and database diagnostics.',
    application: 'Application',
    storageDb: 'Storage and DB',
    confirmImpersonateTitle: 'Start impersonation?',
    confirmImpersonateMessage: 'You will sign in as {email}.',
    confirmResetPasswordTitle: 'Generate temporary password?',
    confirmResetPasswordMessage: 'Temporary password will be generated for {email}.',
    confirmBroadcastTitle: 'Broadcast to all users?',
    confirmBroadcastMessage: 'Notification will be sent to all users.',
    confirmCleanupTitle: 'Run trash cleanup?',
    confirmCleanupMessage: 'All trashed files older than {days} days will be purged.',
    confirmPurgeTitle: 'Delete permanently?',
    confirmPurgeMessage: 'This document will be permanently deleted.',
    confirmBulkPurgeTitle: 'Delete selected permanently?',
    confirmBulkPurgeMessage: 'Selected documents will be permanently deleted.'
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

const ru = {
  ...en,
  nav: {
    ...en.nav,
    home: 'Главная',
    dashboard: 'Панель',
    documents: 'Документы',
    trash: 'Корзина',
    admin: 'Админ',
    developer: 'Разработчик',
    notifications: 'Уведомления',
    markAllRead: 'Прочитать все',
    login: 'Вход',
    register: 'Регистрация',
    logout: 'Выход'
  },
  trash: {
    ...en.trash,
    title: 'Корзина',
    subtitle: 'Восстановите документ или удалите навсегда.',
    deletedDocs: 'Удаленные документы',
    refresh: 'Обновить',
    restore: 'Восстановить',
    deleteForever: 'Удалить навсегда',
    search: 'Поиск в корзине',
    apply: 'Применить',
    reset: 'Сбросить',
    selected: 'Выбрано',
    unknownOwner: 'Неизвестный владелец',
    confirmPurgeTitle: 'Удалить навсегда?',
    confirmPurgeMessage: 'Документ будет удален безвозвратно.',
    confirmPurgeSelectedMessage: 'Выбранные документы будут удалены безвозвратно.'
  },
  admin: {
    ...en.admin,
    title: 'Панель администратора',
    subtitle: 'Управляйте пользователями, ролями, хранилищем и корзиной.',
    users: 'Пользователи',
    admins: 'Администраторы',
    developers: 'Разработчики',
    docs: 'Документы',
    usersRoles: 'Пользователи и роли',
    globalTrash: 'Глобальная корзина',
    searchUsers: 'Поиск по имени или email',
    searchTrash: 'Поиск удаленных документов',
    restore: 'Восстановить',
    purge: 'Удалить',
    confirmPurgeTitle: 'Удалить навсегда?',
    confirmPurgeMessage: 'Это действие нельзя отменить.'
  },
  details: {
    ...en.details,
    download: 'Скачать',
    share: 'Поделиться',
    delete: 'Удалить',
    preview: 'Предпросмотр',
    saveMeta: 'Сохранить метаданные',
    uploadVersion: 'Загрузить новую версию',
    createVersion: 'Создать версию',
    inlineEditor: 'Встроенный редактор',
    saveAsVersion: 'Сохранить как новую версию',
    reload: 'Перезагрузить',
    owner: 'Владелец',
    currentVersion: 'Текущая версия',
    versionHistory: 'История версий',
    activity: 'Активность',
    restore: 'Восстановить',
    comments: 'Комментарии',
    addComment: 'Добавить комментарий',
    writeComment: 'Напишите комментарий',
    postComment: 'Отправить',
    confirmMoveToTrashTitle: 'Переместить в корзину?',
    confirmMoveToTrashMessage: 'Документ будет перемещен в корзину, где его можно восстановить.'
  },
  developer: {
    ...en.developer,
    title: 'Панель разработчика',
    subtitle: 'Диагностика приложения, хранилища и базы данных.',
    confirmImpersonateTitle: 'Начать impersonation?',
    confirmImpersonateMessage: 'Вы войдете как {email}.',
    confirmResetPasswordTitle: 'Сгенерировать временный пароль?',
    confirmResetPasswordMessage: 'Для {email} будет сгенерирован временный пароль.',
    confirmBroadcastTitle: 'Сделать рассылку всем?',
    confirmBroadcastMessage: 'Уведомление будет отправлено всем пользователям.',
    confirmCleanupTitle: 'Запустить очистку корзины?',
    confirmCleanupMessage: 'Файлы в корзине старше {days} дней будут удалены.',
    confirmPurgeTitle: 'Удалить навсегда?',
    confirmPurgeMessage: 'Документ будет удален безвозвратно.',
    confirmBulkPurgeTitle: 'Удалить выбранные навсегда?',
    confirmBulkPurgeMessage: 'Выбранные документы будут удалены безвозвратно.'
  },
  common: {
    ...en.common,
    general: 'Общее',
    noDescription: 'Нет описания',
    yes: 'Да',
    no: 'Нет',
    confirmDelete: 'Удалить навсегда?',
    movedToTrash: 'Документ перемещен в корзину'
  }
}

const messages = {
  en,
  ru
}

const resolveKey = (dictionary, key) =>
  key.split('.').reduce((acc, part) => (acc && acc[part] !== undefined ? acc[part] : null), dictionary)

const LOCALE_KEY = 'docbox_locale'
const getInitialLocale = () => {
  const saved = localStorage.getItem(LOCALE_KEY)
  if (saved && messages[saved]) return saved
  const browser = (navigator.language || 'en').toLowerCase()
  if (browser.startsWith('ru')) return 'ru'
  return 'en'
}

let currentLocale = getInitialLocale()

export const t = (key) => {
  const value = resolveKey(messages[currentLocale], key)
  if (value !== null && value !== undefined) return value
  const fallback = resolveKey(messages.en, key)
  return fallback !== null && fallback !== undefined ? fallback : key
}

export const setLocale = (locale) => {
  if (!messages[locale]) return
  currentLocale = locale
  localStorage.setItem(LOCALE_KEY, locale)
}

export const getLocale = () => currentLocale

export const useI18n = () => ({ t, setLocale, getLocale })

export default {
  install(app) {
    app.provide('docboxI18n', { t, setLocale, getLocale })
    app.config.globalProperties.$t = t
  }
}
