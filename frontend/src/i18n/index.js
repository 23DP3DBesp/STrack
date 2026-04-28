import { createI18n } from 'vue-i18n'

const savedLocale = localStorage.getItem('car_tracker_locale') || 'lv'

const messages = {
  en: {
    nav: {
      home: 'Home',
      login: 'Login',
      register: 'Register',
      dashboard: 'Dashboard',
      profile: 'Profile',
      logout: 'Logout',
      language: 'Language'
    },

    home: {
      kicker: 'Car Tracker & Tuning Journal',
      title: 'Track every refill, repair, and upgrade in one clean garage workspace.',
      subtitle:
        'Keep a searchable garage, monitor fuel costs, log maintenance, and build a full tuning history for each car.',
      start: 'Start Tracking',
      signIn: 'Sign In',
      cards: {
        cars: {
          label: 'Cars',
          value: 'Garage inventory with instant search'
        },
        fuel: {
          label: 'Fuel Logs',
          value: 'Auto-calculated price per liter and consumption'
        },
        repairs: {
          label: 'Repairs',
          value: 'Date filtering for service history'
        },
        mods: {
          label: 'Mods',
          value: 'Tuning journal with cost and impact notes'
        }
      }
    },

    auth: {
      loginTitle: 'Log in to your account',
      continue: 'Continue to your garage',
      email: 'Email',
      login: 'Login',
      password: 'Password',
      passwordConfirm: 'Confirm password',
      signIn: 'Sign in',
      registerTitle: 'Create new account',
      create: 'Create driver profile',
      signUp: 'Sign up',
      verifyFirst: 'Please verify your email before logging in.',
      needVerificationHint: 'Your account exists but email confirmation is still pending.',
      resendVerification: 'Resend verification email',
      verifyEmailTitle: 'Verify your email',
      verifyEmailSubtitle:
        'We sent a confirmation link to your email address. Open it to activate your account.',
      verifyEmailSentFallback:
        'Account created, but the verification email could not be sent right now. Check Mailtrap config and resend it.',
      verificationResent:
        'A new verification email has been sent if the account exists and is still unverified.',
      verificationSuccess: 'Your email is verified. You can now sign in.',
      verificationAlreadyDone: 'This email was already verified. You can sign in.',
      verificationInvalid: 'This verification link is invalid or expired. Request a new one.',
      goToLogin: 'Go to login',
      goToRegister: 'Create account',
      sendAgain: 'Send again'
    },

    dashboard: {
      heroKicker: 'Garage dashboard',
      welcome: 'Welcome back',
      subtitle: 'Track ownership costs, monitor history, and keep a cleaner garage record.',
      addCar: 'Add car',
      refresh: 'Refresh',
      cars: 'Cars',
      fuelLogs: 'Fuel Logs',
      repairs: 'Repairs',
      totalSpend: 'Total Spend',
      selectedPeriod: 'Selected period',
      costPerKm: 'Cost per km',
      distanceTracked: 'Distance tracked',
      periodFilter: 'Period filter',
      periodFilterSubtitle: 'Apply the same time range to analytics and insights',
      allTime: 'All time',
      months3: '3 months',
      months6: '6 months',
      months12: '12 months',
      fuelConsumptionByMonth: 'Fuel consumption by month',
      selectCarFuelTrend: 'Select a car to see fuel trends',
      needFuelLogsForChart:
        'Add at least two fuel logs for one car to build the monthly consumption chart.',
      monthlyExpenseBreakdown: 'Monthly expense breakdown',
      expenseBreakdownSubtitle: 'Fuel, repairs, and mods grouped by month for the selected car',
      needExpensesForBreakdown:
        'Add fuel logs, repairs, or mods to build a monthly expense breakdown.',
      carsSectionTitle: 'Cars',
      searchByBrandModel: 'Search by brand or model',
      searchCars: 'Search cars',
      addFirstCar: 'Add your first car to start tracking fuel, repairs, and mods.',
      selectCarToManage: 'Select a car to manage its journal.',
      fuelTab: 'Fuel Logs',
      repairsTab: 'Repairs',
      modsTab: 'Mods',
      fuelingDate: 'Fueling date',
      repairDateFrom: 'Repair date from',
      apply: 'Apply',
      reset: 'Reset',
      add: 'Add',
      date: 'Date',
      liters: 'Liters',
      total: 'Total',
      pricePerLiter: 'Price/L',
      mileage: 'Mileage',
      consumption: 'Consumption',
      actions: 'Actions',
      edit: 'Edit',
      delete: 'Delete',
      pendingNextLog: 'Pending next log',
      type: 'Type',
      cost: 'Cost',
      description: 'Description',
      dateInstalled: 'Date installed',
      name: 'Name',
      performanceImpact: 'Performance impact',
      vehicle: 'Vehicle',
      plate: 'Plate',
      insurance: 'Insurance',
      technicalInspection: 'Technical inspection',
      totalFuelSpend: 'Total fuel spend',
      serviceAndMods: 'Service + mods',
      trackedOwnershipTotal: 'Tracked ownership total',

      editCar: 'Edit car',
      updateExpiryDates: 'Update expiry dates',
      brand: 'Brand',
      model: 'Model',
      year: 'Year',
      engineVolume: 'Engine volume',
      licensePlate: 'License plate',
      insuranceUntil: 'Insurance until',
      inspectionUntil: 'Inspection until',
      addFuelLog: 'Add fuel log',
      editFuelLog: 'Edit fuel log',
      save: 'Save',
      cancel: 'Cancel',
      fuelConsumptionCalculated:
        'Fuel consumption is calculated from the previous log for the same car.',
      addRepair: 'Add repair',
      editRepair: 'Edit repair',
      addMod: 'Add mod',
      editMod: 'Edit mod',
      dateInstall: 'Date installed',
      selectInsuranceExpiry: 'Select insurance expiry',
      selectInspectionExpiry: 'Select inspection expiry',
      selectFuelingDate: 'Select fueling date',
      selectRepairDate: 'Select repair date',
      selectInstallDate: 'Select install date',
      confirmDelete: 'Confirm delete',
      deleteCarConfirm: 'This removes all fuel logs, repairs, and mods.',
      required: 'Required'
    }
  },

  lv: {
    nav: {
      home: 'Sākums',
      login: 'Ieiet',
      register: 'Reģistrēties',
      dashboard: 'Panelis',
      profile: 'Profils',
      logout: 'Iziet',
      language: 'Valoda'
    },

    home: {
      kicker: 'Auto uzskaite un uzlabojumu žurnāls',
      title: 'Uzskaiti katru uzpildi, remontu un uzlabojumu vienā ērtā garāžas darbvietā.',
      subtitle:
        'Pārvaldi savu garāžu, seko degvielas izmaksām, reģistrē apkopi un veido pilnu katra auto vēsturi.',
      start: 'Sākt uzskaiti',
      signIn: 'Ieiet',
      cards: {
        cars: {
          label: 'Auto',
          value: 'Garāžas saraksts ar ātru meklēšanu'
        },
        fuel: {
          label: 'Degvielas ieraksti',
          value: 'Automātiski aprēķināta cena par litru un patēriņš'
        },
        repairs: {
          label: 'Remonti',
          value: 'Datumu filtrēšana servisa vēsturei'
        },
        mods: {
          label: 'Modifikācijas',
          value: 'Uzlabojumu žurnāls ar izmaksām un ietekmes piezīmēm'
        }
      }
    },

    auth: {
      loginTitle: 'Ienāc savā kontā',
      continue: 'Turpini uz savu garāžu',
      email: 'E-pasts',
      login: 'Lietotājvārds',
      password: 'Parole',
      passwordConfirm: 'Apstiprini paroli',
      signIn: 'Ieiet',
      registerTitle: 'Izveido jaunu kontu',
      create: 'Izveido vadītāja profilu',
      signUp: 'Reģistrēties',
      verifyFirst: 'Pirms ieiešanas apstiprini savu e-pastu.',
      needVerificationHint: 'Konts ir izveidots, bet e-pasta apstiprinājums vēl nav pabeigts.',
      resendVerification: 'Nosūtīt apstiprinājuma e-pastu vēlreiz',
      verifyEmailTitle: 'Apstiprini e-pastu',
      verifyEmailSubtitle:
        'Mēs nosūtījām apstiprinājuma saiti uz tavu e-pastu. Atver to, lai aktivizētu kontu.',
      verifyEmailSentFallback:
        'Konts ir izveidots, bet apstiprinājuma e-pastu pašlaik nevarēja nosūtīt. Pārbaudi Mailtrap konfigurāciju un nosūti vēlreiz.',
      verificationResent: 'Ja konts eksistē un vēl nav apstiprināts, jauns e-pasts tika nosūtīts.',
      verificationSuccess: 'E-pasts ir apstiprināts. Tagad vari ieiet.',
      verificationAlreadyDone: 'Šis e-pasts jau bija apstiprināts. Vari ieiet.',
      verificationInvalid: 'Šī apstiprinājuma saite ir nederīga vai beigusies. Pieprasi jaunu.',
      goToLogin: 'Uz ieiešanu',
      goToRegister: 'Izveidot kontu',
      sendAgain: 'Nosūtīt vēlreiz'
    },

    dashboard: {
      heroKicker: 'Garažas panelis',
      welcome: 'Sveiks atpakaļ',
      subtitle:
        'Seko īpašumtiesību izmaksām, pārskati vēsturi un uzturi sakārtotu garāžas ierakstu.',
      addCar: 'Pievienot auto',
      refresh: 'Atjaunot',
      cars: 'Auto',
      fuelLogs: 'Degvielas ieraksti',
      repairs: 'Remonti',
      totalSpend: 'Kopējie tēriņi',
      selectedPeriod: 'Izvēlētais periods',
      costPerKm: 'Izmaksas uz km',
      distanceTracked: 'Uzskaitītais attālums',
      periodFilter: 'Perioda filtrs',
      periodFilterSubtitle: 'Pielieto vienu periodu analītikai un ieskatiem',
      allTime: 'Visu laiku',
      months3: '3 mēneši',
      months6: '6 mēneši',
      months12: '12 mēneši',
      fuelConsumptionByMonth: 'Degvielas patēriņš pa mēnešiem',
      selectCarFuelTrend: 'Izvēlies auto, lai redzētu degvielas tendences',
      needFuelLogsForChart:
        'Pievieno vismaz divus degvielas ierakstus, lai izveidotu mēneša patēriņa diagrammu.',
      monthlyExpenseBreakdown: 'Mēneša izdevumu sadalījums',
      expenseBreakdownSubtitle: 'Degviela, remonti un modifikācijas pa mēnešiem izvēlētajam auto',
      needExpensesForBreakdown:
        'Pievieno degvielas ierakstus, remontus vai modifikācijas, lai izveidotu mēneša izdevumu sadalījumu.',
      carsSectionTitle: 'Auto',
      searchByBrandModel: 'Meklē pēc markas vai modeļa',
      searchCars: 'Meklēt auto',
      addFirstCar:
        'Pievieno savu pirmo auto, lai sāktu sekot degvielai, remontiem un modifikācijām.',
      selectCarToManage: 'Izvēlies auto, lai pārvaldītu tā žurnālu.',
      fuelTab: 'Degvielas ieraksti',
      repairsTab: 'Remonti',
      modsTab: 'Modifikācijas',
      fuelingDate: 'Uzpildes datums',
      repairDateFrom: 'Remonta datums no',
      apply: 'Piemērot',
      reset: 'Atiestatīt',
      add: 'Pievienot',
      date: 'Datums',
      liters: 'Litri',
      total: 'Kopā',
      pricePerLiter: 'Cena/L',
      mileage: 'Nobraukums',
      consumption: 'Patēriņš',
      actions: 'Darbības',
      edit: 'Rediģēt',
      delete: 'Dzēst',
      pendingNextLog: 'Gaida nākamo ierakstu',
      type: 'Tips',
      cost: 'Izmaksas',
      description: 'Apraksts',
      dateInstalled: 'Uzstādīšanas datums',
      name: 'Nosaukums',
      performanceImpact: 'Veiktspējas ietekme',
      vehicle: 'Transportlīdzeklis',
      plate: 'Numurzīme',
      insurance: 'Apdrošināšana',
      technicalInspection: 'Tehniskā apskate',
      totalFuelSpend: 'Kopējās degvielas izmaksas',
      serviceAndMods: 'Serviss + modi',
      trackedOwnershipTotal: 'Kopējās uzskaitītās izmaksas',

      editCar: 'Rediģēt auto',
      updateExpiryDates: 'Atjaunot termiņu beigās',
      brand: 'Marka',
      model: 'Modelis',
      year: 'Gads',
      engineVolume: 'Dzinēja tilpums',
      licensePlate: 'Numurzīme',
      insuranceUntil: 'Apdrošināšana līdz',
      inspectionUntil: 'Apskate līdz',
      addFuelLog: 'Pievienot degvielas ierakstu',
      editFuelLog: 'Rediģēt degvielas ierakstu',
      save: 'Saglabāt',
      cancel: 'Atcelt',
      fuelConsumptionCalculated:
        'Degvielas patēriņš tiek aprēķināts no iepriekšējā ieraksta tam pašam auto.',
      addRepair: 'Pievienot remontu',
      editRepair: 'Rediģēt remontu',
      addMod: 'Pievienot modifikāciju',
      editMod: 'Rediģēt modifikāciju',
      dateInstall: 'Uzstādīšanas datums',
      selectInsuranceExpiry: 'Izvēlies apdrošināšanas termiņa beigas',
      selectInspectionExpiry: 'Izvēlies apskates termiņa beigas',
      selectFuelingDate: 'Izvēlies uzpildes datumu',
      selectRepairDate: 'Izvēlies remonta datumu',
      selectInstallDate: 'Izvēlies uzstādīšanas datumu',
      confirmDelete: 'Apstiprini dzēšanu',
      deleteCarConfirm: 'Tas dzēsīs visus degvielas ierakstus, remontus un modifikācijas.',
      required: 'Obligāts'
    }
  }
}

export const i18n = createI18n({
  legacy: false,
  locale: savedLocale,
  fallbackLocale: 'en',
  messages
})

export const setAppLocale = (locale) => {
  const nextLocale = locale === 'lv' ? 'lv' : 'en'
  i18n.global.locale.value = nextLocale
  localStorage.setItem('car_tracker_locale', nextLocale)
}

export default i18n
