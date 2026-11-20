import dotenv from "dotenv";
dotenv.config({ path: ".env.local" });
dotenv.config();
import { PrismaClient } from "@prisma/client";
import { PrismaPg } from "@prisma/adapter-pg";
import { hash } from "bcryptjs";

const adapter = new PrismaPg({ connectionString: process.env.DATABASE_URL! });
const prisma = new PrismaClient({ adapter });

const DRC_PROVINCES = [
  { code: "KIN", name: "Kinshasa" },
  { code: "KON", name: "Kongo Central" },
  { code: "KWA", name: "Kwango" },
  { code: "KWL", name: "Kwilu" },
  { code: "MAI", name: "Mai-Ndombe" },
  { code: "KAS", name: "Kasaï" },
  { code: "KSC", name: "Kasaï-Central" },
  { code: "KSO", name: "Kasaï-Oriental" },
  { code: "LOM", name: "Lomami" },
  { code: "SKN", name: "Sankuru" },
  { code: "MAN", name: "Maniema" },
  { code: "SKS", name: "South Kivu" },
  { code: "NKS", name: "North Kivu" },
  { code: "ITU", name: "Ituri" },
  { code: "HUT", name: "Haut-Uele" },
  { code: "BAS", name: "Bas-Uele" },
  { code: "TSH", name: "Tshopo" },
  { code: "TNG", name: "Tanganyika" },
  { code: "HAK", name: "Haut-Katanga" },
  { code: "LUL", name: "Lualaba" },
  { code: "HAK2", name: "Haut-Lomami" },
  { code: "LUA", name: "Luapula" },
  { code: "MNG", name: "Mongala" },
  { code: "NOR", name: "Nord-Ubangi" },
  { code: "SUD", name: "Sud-Ubangi" },
  { code: "EQU", name: "Équateur" },
  { code: "TSH2", name: "Tshuapa" },
];

const HEALTH_ZONES_BY_PROVINCE: Record<string, Array<{ code: string; name: string }>> = {
  KIN: [
    { code: "KIN01", name: "Kinshasa" },
    { code: "KIN02", name: "Kintambo" },
    { code: "KIN03", name: "Limete" },
    { code: "KIN04", name: "Matonge" },
    { code: "KIN05", name: "Ngaliema" },
    { code: "KIN06", name: "N'Djili" },
    { code: "KIN07", name: "Bumbu" },
    { code: "KIN08", name: "Kalamu" },
  ],
  KON: [
    { code: "KON01", name: "Matadi" },
    { code: "KON02", name: "Boma" },
    { code: "KON03", name: "Mbanza-Ngungu" },
    { code: "KON04", name: "Kisantu" },
  ],
  KWA: [
    { code: "KWA01", name: "Kenge" },
    { code: "KWA02", name: "Feshi" },
    { code: "KWA03", name: "Kahemba" },
  ],
  KWL: [
    { code: "KWL01", name: "Bandundu" },
    { code: "KWL02", name: "Kikwit" },
    { code: "KWL03", name: "Idiofa" },
    { code: "KWL04", name: "Gungu" },
  ],
  MAI: [
    { code: "MAI01", name: "Inongo" },
    { code: "MAI02", name: "Kutu" },
    { code: "MAI03", name: "Kirii" },
  ],
  KAS: [
    { code: "KAS01", name: "Tshikapa" },
    { code: "KAS02", name: "Luebo" },
    { code: "KAS03", name: "Mweka" },
  ],
  KSC: [
    { code: "KSC01", name: "Kananga" },
    { code: "KSC02", name: "Dibaya" },
    { code: "KSC03", name: "Luiza" },
  ],
  KSO: [
    { code: "KSO01", name: "Mbuji-Mayi" },
    { code: "KSO02", name: "Miabi" },
    { code: "KSO03", name: "Tshilenge" },
  ],
  LOM: [
    { code: "LOM01", name: "Kabinda" },
    { code: "LOM02", name: "Ngandajika" },
    { code: "LOM03", name: "Lubao" },
  ],
  SKN: [
    { code: "SKN01", name: "Lodja" },
    { code: "SKN02", name: "Katako-Kombe" },
    { code: "SKN03", name: "Kalebuka" },
  ],
  MAN: [
    { code: "MAN01", name: "Kindu" },
    { code: "MAN02", name: "Kibombo" },
    { code: "MAN03", name: "Kailo" },
  ],
  SKS: [
    { code: "SKS01", name: "Bukavu" },
    { code: "SKS02", name: "Uvira" },
    { code: "SKS03", name: "Shabunda" },
    { code: "SKS04", name: "Mwenga" },
  ],
  NKS: [
    { code: "NKS01", name: "Goma" },
    { code: "NKS02", name: "Beni" },
    { code: "NKS03", name: "Butembo" },
    { code: "NKS04", name: "Rutshuru" },
  ],
  ITU: [
    { code: "ITU01", name: "Bunia" },
    { code: "ITU02", name: "Mahagi" },
    { code: "ITU03", name: "Aru" },
  ],
  HUT: [
    { code: "HUT01", name: "Isiro" },
    { code: "HUT02", name: "Watsa" },
    { code: "HUT03", name: "Dungu" },
  ],
  BAS: [
    { code: "BAS01", name: "Buta" },
    { code: "BAS02", name: "Bondo" },
    { code: "BAS03", name: "Ango" },
  ],
  TSH: [
    { code: "TSH01", name: "Kisangani" },
    { code: "TSH02", name: "Bafwasende" },
    { code: "TSH03", name: "Opala" },
  ],
  TNG: [
    { code: "TNG01", name: "Kalemie" },
    { code: "TNG02", name: "Kabalo" },
    { code: "TNG03", name: "Kongolo" },
  ],
  HAK: [
    { code: "HAK01", name: "Lubumbashi" },
    { code: "HAK02", name: "Kipushi" },
    { code: "HAK03", name: "Kasenga" },
    { code: "HAK04", name: "Sakania" },
  ],
  LUL: [
    { code: "LUL01", name: "Kolwezi" },
    { code: "LUL02", name: "Dilala" },
    { code: "LUL03", name: "Mutshatsha" },
  ],
  HAK2: [
    { code: "HAK201", name: "Kamina" },
    { code: "HAK202", name: "Malemba-Nkulu" },
    { code: "HAK203", name: "Bukama" },
  ],
  LUA: [
    { code: "LUA01", name: "Mansa" },
    { code: "LUA02", name: "Samfya" },
    { code: "LUA03", name: "Mwense" },
  ],
  MNG: [
    { code: "MNG01", name: "Lisala" },
    { code: "MNG02", name: "Bumba" },
    { code: "MNG03", name: "Bongandanga" },
  ],
  NOR: [
    { code: "NOR01", name: "Gbadolite" },
    { code: "NOR02", name: "Mobayi-Mbongo" },
    { code: "NOR03", name: "Yakoma" },
  ],
  SUD: [
    { code: "SUD01", name: "Gemena" },
    { code: "SUD02", name: "Libenge" },
    { code: "SUD03", name: "Budjala" },
  ],
  EQU: [
    { code: "EQU01", name: "Mbandaka" },
    { code: "EQU02", name: "Bikoro" },
    { code: "EQU03", name: "Ingende" },
  ],
  TSH2: [
    { code: "TSH201", name: "Boende" },
    { code: "TSH202", name: "Bokungu" },
    { code: "TSH203", name: "Ikela" },
  ],
};

const HEALTH_AREAS_BY_ZONE: Record<string, Array<{ code: string; name: string }>> = {
  KIN01: [{ code: "KIN01A01", name: "Kinshasa Centre" }, { code: "KIN01A02", name: "Gombe" }, { code: "KIN01A03", name: "Barumbu" }],
  KIN02: [{ code: "KIN02A01", name: "Kintambo Centre" }, { code: "KIN02A02", name: "Ngamakala" }],
  KIN03: [{ code: "KIN03A01", name: "Limete Centre" }, { code: "KIN03A02", name: "Kingabwa" }],
  KIN04: [{ code: "KIN04A01", name: "Matonge Centre" }, { code: "KIN04A02", name: "Kalembelembe" }],
  KIN05: [{ code: "KIN05A01", name: "Ngaliema Centre" }, { code: "KIN05A02", name: "Binza" }],
  KIN06: [{ code: "KIN06A01", name: "N'Djili Centre" }, { code: "KIN06A02", name: "Kimbanseke" }],
  KIN07: [{ code: "KIN07A01", name: "Bumbu Centre" }, { code: "KIN07A02", name: "Selembao" }],
  KIN08: [{ code: "KIN08A01", name: "Kalamu Centre" }, { code: "KIN08A02", name: "Kimanga" }],
  KON01: [{ code: "KON01A01", name: "Matadi Centre" }, { code: "KON01A02", name: "M'pozo" }],
  KON02: [{ code: "KON02A01", name: "Boma Centre" }, { code: "KON02A02", name: "Muanda" }],
  KON03: [{ code: "KON03A01", name: "Mbanza-Ngungu Centre" }],
  KON04: [{ code: "KON04A01", name: "Kisantu Centre" }],
  KWA01: [{ code: "KWA01A01", name: "Kenge Centre" }],
  KWA02: [{ code: "KWA02A01", name: "Feshi Centre" }],
  KWA03: [{ code: "KWA03A01", name: "Kahemba Centre" }],
  KWL01: [{ code: "KWL01A01", name: "Bandundu Centre" }],
  KWL02: [{ code: "KWL02A01", name: "Kikwit Centre" }],
  KWL03: [{ code: "KWL03A01", name: "Idiofa Centre" }],
  KWL04: [{ code: "KWL04A01", name: "Gungu Centre" }],
  MAI01: [{ code: "MAI01A01", name: "Inongo Centre" }],
  MAI02: [{ code: "MAI02A01", name: "Kutu Centre" }],
  MAI03: [{ code: "MAI03A01", name: "Kirii Centre" }],
  KAS01: [{ code: "KAS01A01", name: "Tshikapa Centre" }],
  KAS02: [{ code: "KAS02A01", name: "Luebo Centre" }],
  KAS03: [{ code: "KAS03A01", name: "Mweka Centre" }],
  KSC01: [{ code: "KSC01A01", name: "Kananga Centre" }],
  KSC02: [{ code: "KSC02A01", name: "Dibaya Centre" }],
  KSC03: [{ code: "KSC03A01", name: "Luiza Centre" }],
  KSO01: [{ code: "KSO01A01", name: "Mbuji-Mayi Centre" }],
  KSO02: [{ code: "KSO02A01", name: "Miabi Centre" }],
  KSO03: [{ code: "KSO03A01", name: "Tshilenge Centre" }],
  LOM01: [{ code: "LOM01A01", name: "Kabinda Centre" }],
  LOM02: [{ code: "LOM02A01", name: "Ngandajika Centre" }],
  LOM03: [{ code: "LOM03A01", name: "Lubao Centre" }],
  SKN01: [{ code: "SKN01A01", name: "Lodja Centre" }],
  SKN02: [{ code: "SKN02A01", name: "Katako-Kombe Centre" }],
  SKN03: [{ code: "SKN03A01", name: "Kalebuka Centre" }],
  MAN01: [{ code: "MAN01A01", name: "Kindu Centre" }],
  MAN02: [{ code: "MAN02A01", name: "Kibombo Centre" }],
  MAN03: [{ code: "MAN03A01", name: "Kailo Centre" }],
  SKS01: [{ code: "SKS01A01", name: "Bukavu Centre" }, { code: "SKS01A02", name: "Ibanda" }],
  SKS02: [{ code: "SKS02A01", name: "Uvira Centre" }],
  SKS03: [{ code: "SKS03A01", name: "Shabunda Centre" }],
  SKS04: [{ code: "SKS04A01", name: "Mwenga Centre" }],
  NKS01: [{ code: "NKS01A01", name: "Goma Centre" }, { code: "NKS01A02", name: "Nyiragongo" }],
  NKS02: [{ code: "NKS02A01", name: "Beni Centre" }],
  NKS03: [{ code: "NKS03A01", name: "Butembo Centre" }],
  NKS04: [{ code: "NKS04A01", name: "Rutshuru Centre" }],
  ITU01: [{ code: "ITU01A01", name: "Bunia Centre" }],
  ITU02: [{ code: "ITU02A01", name: "Mahagi Centre" }],
  ITU03: [{ code: "ITU03A01", name: "Aru Centre" }],
  HUT01: [{ code: "HUT01A01", name: "Isiro Centre" }],
  HUT02: [{ code: "HUT02A01", name: "Watsa Centre" }],
  HUT03: [{ code: "HUT03A01", name: "Dungu Centre" }],
  BAS01: [{ code: "BAS01A01", name: "Buta Centre" }],
  BAS02: [{ code: "BAS02A01", name: "Bondo Centre" }],
  BAS03: [{ code: "BAS03A01", name: "Ango Centre" }],
  TSH01: [{ code: "TSH01A01", name: "Kisangani Centre" }],
  TSH02: [{ code: "TSH02A01", name: "Bafwasende Centre" }],
  TSH03: [{ code: "TSH03A01", name: "Opala Centre" }],
  TNG01: [{ code: "TNG01A01", name: "Kalemie Centre" }],
  TNG02: [{ code: "TNG02A01", name: "Kabalo Centre" }],
  TNG03: [{ code: "TNG03A01", name: "Kongolo Centre" }],
  HAK01: [{ code: "HAK01A01", name: "Lubumbashi Centre" }, { code: "HAK01A02", name: "Katuba" }],
  HAK02: [{ code: "HAK02A01", name: "Kipushi Centre" }],
  HAK03: [{ code: "HAK03A01", name: "Kasenga Centre" }],
  HAK04: [{ code: "HAK04A01", name: "Sakania Centre" }],
  LUL01: [{ code: "LUL01A01", name: "Kolwezi Centre" }],
  LUL02: [{ code: "LUL02A01", name: "Dilala Centre" }],
  LUL03: [{ code: "LUL03A01", name: "Mutshatsha Centre" }],
  HAK201: [{ code: "HAK201A01", name: "Kamina Centre" }],
  HAK202: [{ code: "HAK202A01", name: "Malemba-Nkulu Centre" }],
  HAK203: [{ code: "HAK203A01", name: "Bukama Centre" }],
  LUA01: [{ code: "LUA01A01", name: "Mansa Centre" }],
  LUA02: [{ code: "LUA02A01", name: "Samfya Centre" }],
  LUA03: [{ code: "LUA03A01", name: "Mwense Centre" }],
  MNG01: [{ code: "MNG01A01", name: "Lisala Centre" }],
  MNG02: [{ code: "MNG02A01", name: "Bumba Centre" }],
  MNG03: [{ code: "MNG03A01", name: "Bongandanga Centre" }],
  NOR01: [{ code: "NOR01A01", name: "Gbadolite Centre" }],
  NOR02: [{ code: "NOR02A01", name: "Mobayi-Mbongo Centre" }],
  NOR03: [{ code: "NOR03A01", name: "Yakoma Centre" }],
  SUD01: [{ code: "SUD01A01", name: "Gemena Centre" }],
  SUD02: [{ code: "SUD02A01", name: "Libenge Centre" }],
  SUD03: [{ code: "SUD03A01", name: "Budjala Centre" }],
  EQU01: [{ code: "EQU01A01", name: "Mbandaka Centre" }],
  EQU02: [{ code: "EQU02A01", name: "Bikoro Centre" }],
  EQU03: [{ code: "EQU03A01", name: "Ingende Centre" }],
  TSH201: [{ code: "TSH201A01", name: "Boende Centre" }],
  TSH202: [{ code: "TSH202A01", name: "Bokungu Centre" }],
  TSH203: [{ code: "TSH203A01", name: "Ikela Centre" }],
};

const HEALTH_CENTERS_BY_AREA: Record<string, Array<{ code: string; name: string; type: "health_center" | "hospital" | "clinic"; latitude?: number; longitude?: number }>> = {
  "KIN01A01": [
    { code: "HC001", name: "Hôpital Général de Référence de Kinshasa", type: "hospital", latitude: -4.325, longitude: 15.3222 },
    { code: "HC002", name: "Centre de Santé Ngaliema", type: "health_center", latitude: -4.35, longitude: 15.28 },
    { code: "HC003", name: "Clinique Nganda", type: "clinic", latitude: -4.33, longitude: 15.3 },
  ],
  "KIN01A02": [
    { code: "HC004", name: "Hôpital du Cinquantenaire", type: "hospital", latitude: -4.3, longitude: 15.33 },
    { code: "HC005", name: "Centre de Santé Gombe", type: "health_center", latitude: -4.31, longitude: 15.34 },
  ],
  "KIN01A03": [
    { code: "HC006", name: "Centre de Santé Barumbu", type: "health_center", latitude: -4.34, longitude: 15.31 },
  ],
  "KIN02A01": [
    { code: "HC007", name: "Hôpital de Kintambo", type: "hospital", latitude: -4.36, longitude: 15.26 },
  ],
  "KIN03A01": [
    { code: "HC008", name: "Centre de Santé Limete", type: "health_center", latitude: -4.38, longitude: 15.36 },
  ],
  "KIN04A01": [
    { code: "HC009", name: "Centre de Santé Matonge", type: "health_center", latitude: -4.33, longitude: 15.31 },
  ],
  "KIN05A01": [
    { code: "HC010", name: "Hôpital de Ngaliema", type: "hospital", latitude: -4.35, longitude: 15.27 },
  ],
  "KIN06A01": [
    { code: "HC011", name: "Centre de Santé N'Djili", type: "health_center", latitude: -4.4, longitude: 15.4 },
  ],
  "KIN07A01": [
    { code: "HC012", name: "Centre de Santé Bumbu", type: "health_center", latitude: -4.42, longitude: 15.35 },
  ],
  "KIN08A01": [
    { code: "HC013", name: "Centre de Santé Kalamu", type: "health_center", latitude: -4.37, longitude: 15.32 },
  ],
  "KON01A01": [
    { code: "HC014", name: "Hôpital Général de Référence de Matadi", type: "hospital", latitude: -5.82, longitude: 13.46 },
  ],
  "KON02A01": [
    { code: "HC015", name: "Hôpital de Boma", type: "hospital", latitude: -5.85, longitude: 13.05 },
  ],
  "KON03A01": [
    { code: "HC016", name: "Centre de Santé Mbanza-Ngungu", type: "health_center", latitude: -5.25, longitude: 14.87 },
  ],
  "KON04A01": [
    { code: "HC017", name: "Hôpital de Kisantu", type: "hospital", latitude: -5.12, longitude: 15.08 },
  ],
  "SKS01A01": [
    { code: "HC018", name: "Hôpital Général de Référence de Bukavu", type: "hospital", latitude: -2.51, longitude: 28.85 },
  ],
  "SKS02A01": [
    { code: "HC019", name: "Hôpital d'Uvira", type: "hospital", latitude: -3.39, longitude: 29.14 },
  ],
  "NKS01A01": [
    { code: "HC020", name: "Hôpital Général de Référence de Goma", type: "hospital", latitude: -1.67, longitude: 29.23 },
  ],
  "NKS02A01": [
    { code: "HC021", name: "Hôpital de Beni", type: "hospital", latitude: 0.49, longitude: 29.47 },
  ],
  "ITU01A01": [
    { code: "HC022", name: "Hôpital de Bunia", type: "hospital", latitude: 1.57, longitude: 30.24 },
  ],
  "HAK01A01": [
    { code: "HC023", name: "Hôpital Général de Référence de Lubumbashi", type: "hospital", latitude: -11.66, longitude: 27.48 },
    { code: "HC024", name: "Centre de Santé Kenya", type: "health_center", latitude: -11.68, longitude: 27.5 },
  ],
  "HAK01A02": [
    { code: "HC025", name: "Centre de Santé Katuba", type: "health_center", latitude: -11.7, longitude: 27.45 },
  ],
  "LUL01A01": [
    { code: "HC026", name: "Hôpital de Kolwezi", type: "hospital", latitude: -10.71, longitude: 25.46 },
  ],
};

const VACCINES = [
  { code: "BCG", name: "BCG (Tuberculose)", description: "Vaccin contre la tuberculose" },
  { code: "HEPB_BD", name: "Hépatite B (dose à la naissance)", description: "Vaccin contre l'hépatite B, dose à la naissance" },
  { code: "DTC_HEPB_HIB_1", name: "DTC-Hépatite B-Hib (1ère dose)", description: "Diphtérie, Tétanos, Coqueluche, Hépatite B, Haemophilus influenzae type b - 1ère dose" },
  { code: "DTC_HEPB_HIB_2", name: "DTC-Hépatite B-Hib (2ème dose)", description: "Diphtérie, Tétanos, Coqueluche, Hépatite B, Haemophilus influenzae type b - 2ème dose" },
  { code: "DTC_HEPB_HIB_3", name: "DTC-Hépatite B-Hib (3ème dose)", description: "Diphtérie, Tétanos, Coqueluche, Hépatite B, Haemophilus influenzae type b - 3ème dose" },
  { code: "POLIO_0", name: "Polio (dose à la naissance)", description: "Vaccin antipoliomyélitique oral, dose à la naissance" },
  { code: "POLIO_1", name: "Polio (1ère dose)", description: "Vaccin antipoliomyélitique oral, 1ère dose" },
  { code: "POLIO_2", name: "Polio (2ème dose)", description: "Vaccin antipoliomyélitique oral, 2ème dose" },
  { code: "POLIO_3", name: "Polio (3ème dose)", description: "Vaccin antipoliomyélitique oral, 3ème dose" },
  { code: "IPV", name: "IPV (Polio inactivé)", description: "Vaccin antipoliomyélitique inactivé" },
  { code: "ROTA_1", name: "Rotavirus (1ère dose)", description: "Vaccin contre le rotavirus, 1ère dose" },
  { code: "ROTA_2", name: "Rotavirus (2ème dose)", description: "Vaccin contre le rotavirus, 2ème dose" },
  { code: "PCV_1", name: "Pneumocoque (1ère dose)", description: "Vaccin conjugué anti-pneumococcique, 1ère dose" },
  { code: "PCV_2", name: "Pneumocoque (2ème dose)", description: "Vaccin conjugué anti-pneumococcique, 2ème dose" },
  { code: "PCV_3", name: "Pneumocoque (3ème dose)", description: "Vaccin conjugué anti-pneumococcique, 3ème dose" },
  { code: "VAR_1", name: "Variole du singe (1ère dose)", description: "Vaccin contre la variole du singe, 1ère dose" },
  { code: "VAR_2", name: "Variole du singe (2ème dose)", description: "Vaccin contre la variole du singe, 2ème dose" },
  { code: "RROU_1", name: "Rougeole-Rubéole (1ère dose)", description: "Vaccin combiné rougeole-rubéole, 1ère dose" },
  { code: "RROU_2", name: "Rougeole-Rubéole (2ème dose)", description: "Vaccin combiné rougeole-rubéole, 2ème dose" },
  { code: "FIEVRE_JAUNE", name: "Fièvre jaune", description: "Vaccin contre la fièvre jaune" },
  { code: "MENINGO_A", name: "Méningocoque A", description: "Vaccin contre le méningocoque de sérogroupe A" },
  { code: "HPV_1", name: "HPV (1ère dose)", description: "Vaccin contre le papillomavirus humain, 1ère dose" },
  { code: "HPV_2", name: "HPV (2ème dose)", description: "Vaccin contre le papillomavirus humain, 2ème dose" },
  { code: "COVID_1", name: "COVID-19 (1ère dose)", description: "Vaccin contre la COVID-19, 1ère dose" },
  { code: "COVID_2", name: "COVID-19 (2ème dose)", description: "Vaccin contre la COVID-19, 2ème dose" },
  { code: "COVID_RAPPEL", name: "COVID-19 (rappel)", description: "Vaccin contre la COVID-19, dose de rappel" },
];

const VACCINATION_SCHEDULE = [
  { code: "DRC_2024", name: "Calendrier vaccinal RDC 2024", jurisdiction: "CD", program: "PEV", version: "2024", status: "active", effectiveFrom: new Date("2024-01-01") },
];

const DOSES_BY_VACCINE: Record<string, Array<{ code: string; name: string; order: number; earliestDays?: number; recommendedDays: number; latestDays?: number; minIntervalDays?: number; intervalRef?: string }>> = {
  BCG: [{ code: "BCG_1", name: "BCG dose unique", order: 1, recommendedDays: 0 }],
  HEPB_BD: [{ code: "HEPB_BD_1", name: "Hépatite B dose naissance", order: 1, recommendedDays: 0 }],
  DTC_HEPB_HIB_1: [{ code: "DTC_HEPB_HIB_1_1", name: "DTC-Hépatite B-Hib dose 1", order: 1, recommendedDays: 42 }],
  DTC_HEPB_HIB_2: [{ code: "DTC_HEPB_HIB_2_1", name: "DTC-Hépatite B-Hib dose 2", order: 2, recommendedDays: 70, minIntervalDays: 28, intervalRef: "DTC_HEPB_HIB_1_1" }],
  DTC_HEPB_HIB_3: [{ code: "DTC_HEPB_HIB_3_1", name: "DTC-Hépatite B-Hib dose 3", order: 3, recommendedDays: 98, minIntervalDays: 28, intervalRef: "DTC_HEPB_HIB_2_1" }],
  POLIO_0: [{ code: "POLIO_0_1", name: "Polio dose naissance", order: 1, recommendedDays: 0 }],
  POLIO_1: [{ code: "POLIO_1_1", name: "Polio dose 1", order: 1, recommendedDays: 42 }],
  POLIO_2: [{ code: "POLIO_2_1", name: "Polio dose 2", order: 2, recommendedDays: 70, minIntervalDays: 28, intervalRef: "POLIO_1_1" }],
  POLIO_3: [{ code: "POLIO_3_1", name: "Polio dose 3", order: 3, recommendedDays: 98, minIntervalDays: 28, intervalRef: "POLIO_2_1" }],
  IPV: [{ code: "IPV_1", name: "IPV dose unique", order: 1, recommendedDays: 98 }],
  ROTA_1: [{ code: "ROTA_1_1", name: "Rotavirus dose 1", order: 1, recommendedDays: 42, latestDays: 104 }],
  ROTA_2: [{ code: "ROTA_2_1", name: "Rotavirus dose 2", order: 2, recommendedDays: 70, minIntervalDays: 28, intervalRef: "ROTA_1_1", latestDays: 104 }],
  PCV_1: [{ code: "PCV_1_1", name: "Pneumocoque dose 1", order: 1, recommendedDays: 42 }],
  PCV_2: [{ code: "PCV_2_1", name: "Pneumocoque dose 2", order: 2, recommendedDays: 70, minIntervalDays: 28, intervalRef: "PCV_1_1" }],
  PCV_3: [{ code: "PCV_3_1", name: "Pneumocoque dose 3", order: 3, recommendedDays: 98, minIntervalDays: 28, intervalRef: "PCV_2_1" }],
  VAR_1: [{ code: "VAR_1_1", name: "Variole du singe dose 1", order: 1, recommendedDays: 270 }],
  VAR_2: [{ code: "VAR_2_1", name: "Variole du singe dose 2", order: 2, recommendedDays: 300, minIntervalDays: 28, intervalRef: "VAR_1_1" }],
  RROU_1: [{ code: "RROU_1_1", name: "Rougeole-Rubéole dose 1", order: 1, recommendedDays: 270 }],
  RROU_2: [{ code: "RROU_2_1", name: "Rougeole-Rubéole dose 2", order: 2, recommendedDays: 540, minIntervalDays: 180, intervalRef: "RROU_1_1" }],
  FIEVRE_JAUNE: [{ code: "FJ_1", name: "Fièvre jaune dose unique", order: 1, recommendedDays: 270 }],
  MENINGO_A: [{ code: "MEN_A_1", name: "Méningocoque A dose unique", order: 1, recommendedDays: 270 }],
  HPV_1: [{ code: "HPV_1_1", name: "HPV dose 1", order: 1, recommendedDays: 3285 }],
  HPV_2: [{ code: "HPV_2_1", name: "HPV dose 2", order: 2, recommendedDays: 3375, minIntervalDays: 180, intervalRef: "HPV_1_1" }],
  COVID_1: [{ code: "COVID_1_1", name: "COVID-19 dose 1", order: 1, recommendedDays: 3285 }],
  COVID_2: [{ code: "COVID_2_1", name: "COVID-19 dose 2", order: 2, recommendedDays: 3315, minIntervalDays: 21, intervalRef: "COVID_1_1" }],
  COVID_RAPPEL: [{ code: "COVID_R_1", name: "COVID-19 rappel", order: 3, recommendedDays: 3475, minIntervalDays: 180, intervalRef: "COVID_2_1" }],
};

const PERMISSIONS = [
  { code: "users.create", description: "Créer des utilisateurs" },
  { code: "users.read", description: "Lire les utilisateurs" },
  { code: "users.update", description: "Modifier des utilisateurs" },
  { code: "users.delete", description: "Supprimer des utilisateurs" },
  { code: "roles.assign", description: "Assigner des rôles" },
  { code: "roles.revoke", description: "Révoquer des rôles" },
  { code: "provinces.manage", description: "Gérer les provinces" },
  { code: "health_zones.manage", description: "Gérer les zones de santé" },
  { code: "health_areas.manage", description: "Gérer les aires de santé" },
  { code: "health_centers.manage", description: "Gérer les centres de santé" },
  { code: "children.create", description: "Enregistrer des enfants" },
  { code: "children.read", description: "Voir les enfants" },
  { code: "children.update", description: "Modifier les enfants" },
  { code: "appointments.create", description: "Créer des rendez-vous" },
  { code: "appointments.read", description: "Voir les rendez-vous" },
  { code: "appointments.manage", description: "Gérer les rendez-vous" },
  { code: "growth.record", description: "Enregistrer la croissance" },
  { code: "growth.read", description: "Voir la croissance" },
  { code: "vaccination.record", description: "Enregistrer les vaccinations" },
  { code: "vaccination.read", description: "Voir les vaccinations" },
  { code: "vaccination.schedule", description: "Planifier les vaccinations" },
  { code: "medical_records.create", description: "Créer des dossiers médicaux" },
  { code: "medical_records.read", description: "Lire les dossiers médicaux" },
  { code: "nutrition.record", description: "Enregistrer la nutrition" },
  { code: "nutrition.read", description: "Voir la nutrition" },
  { code: "alerts.create", description: "Créer des alertes" },
  { code: "alerts.read", description: "Voir les alertes" },
  { code: "alerts.review", description: "Réviser les alertes" },
  { code: "visits.record", description: "Enregistrer les visites" },
  { code: "visits.read", description: "Voir les visites" },
  { code: "referrals.create", description: "Créer des références" },
  { code: "referrals.read", description: "Voir les références" },
  { code: "referrals.manage", description: "Gérer les références" },
  { code: "observations.record", description: "Enregistrer les observations" },
  { code: "observations.read", description: "Voir les observations" },
  { code: "reports.view", description: "Voir les rapports" },
  { code: "reports.export", description: "Exporter les rapports" },
  { code: "audit.read", description: "Lire les logs d'audit" },
  { code: "settings.manage", description: "Gérer les paramètres" },
  { code: "consent.record", description: "Enregistrer le consentement" },
  { code: "access.request", description: "Demander l'accès" },
  { code: "access.review", description: "Réviser les demandes d'accès" },
];

const ROLE_PERMISSIONS: Record<string, string[]> = {
  super_administrator: PERMISSIONS.map(p => p.code),
  administrator: PERMISSIONS.map(p => p.code),
  national_administrator: [
    "users.read", "users.update", "roles.assign", "roles.revoke",
    "provinces.manage", "health_zones.manage", "health_areas.manage", "health_centers.manage",
    "children.read", "appointments.read", "appointments.manage",
    "growth.read", "vaccination.read", "vaccination.schedule",
    "medical_records.read", "nutrition.read",
    "alerts.read", "alerts.review", "visits.read", "referrals.read", "referrals.manage",
    "observations.read", "reports.view", "reports.export", "audit.read", "settings.manage",
    "consent.record", "access.request", "access.review",
  ],
  provincial_administrator: [
    "users.read", "users.update", "roles.assign", "roles.revoke",
    "health_zones.manage", "health_areas.manage", "health_centers.manage",
    "children.read", "children.update", "appointments.read", "appointments.manage",
    "growth.read", "vaccination.read", "vaccination.schedule",
    "medical_records.read", "nutrition.read",
    "alerts.read", "alerts.review", "visits.read", "referrals.read", "referrals.manage",
    "observations.read", "reports.view", "reports.export", "access.request", "access.review",
  ],
  health_zone_administrator: [
    "users.read", "roles.assign", "roles.revoke",
    "health_areas.manage", "health_centers.manage",
    "children.read", "children.update", "children.create", "appointments.read", "appointments.manage",
    "growth.read", "growth.record", "vaccination.read", "vaccination.record", "vaccination.schedule",
    "medical_records.read", "nutrition.read", "nutrition.record",
    "alerts.read", "alerts.review", "alerts.create", "visits.read", "visits.record",
    "referrals.read", "referrals.create", "referrals.manage",
    "observations.read", "observations.record", "reports.view", "consent.record", "access.request",
  ],
  health_facility_administrator: [
    "users.read", "children.read", "children.update", "children.create", "appointments.read", "appointments.manage",
    "growth.read", "growth.record", "vaccination.read", "vaccination.record", "vaccination.schedule",
    "medical_records.read", "medical_records.create", "nutrition.read", "nutrition.record",
    "alerts.read", "alerts.create", "visits.read", "visits.record",
    "referrals.read", "referrals.create", "observations.read", "observations.record", "reports.view", "consent.record",
  ],
  health_professional: [
    "children.read", "children.update", "children.create", "appointments.read", "appointments.manage",
    "growth.read", "growth.record", "vaccination.read", "vaccination.record", "vaccination.schedule",
    "medical_records.read", "medical_records.create", "nutrition.read", "nutrition.record",
    "alerts.read", "alerts.create", "visits.read", "visits.record",
    "referrals.read", "referrals.create", "observations.read", "observations.record", "reports.view", "consent.record",
  ],
  community_health_worker: [
    "children.read", "children.create", "appointments.read", "appointments.create",
    "growth.read", "growth.record", "vaccination.read", "vaccination.record",
    "nutrition.read", "nutrition.record", "alerts.read", "alerts.create",
    "visits.read", "visits.record", "referrals.read", "referrals.create", "consent.record",
  ],
  health_center_manager: [
    "users.read", "children.read", "children.update", "children.create", "appointments.read", "appointments.manage",
    "growth.read", "growth.record", "vaccination.read", "vaccination.record", "vaccination.schedule",
    "medical_records.read", "medical_records.create", "nutrition.read", "nutrition.record",
    "alerts.read", "alerts.create", "alerts.review", "visits.read", "visits.record",
    "referrals.read", "referrals.create", "referrals.manage", "observations.read", "observations.record", "reports.view", "consent.record",
  ],
  health_supervisor: [
    "users.read", "children.read", "children.update", "appointments.read", "appointments.manage",
    "growth.read", "vaccination.read", "vaccination.schedule", "nutrition.read",
    "alerts.read", "alerts.review", "visits.read", "referrals.read", "referrals.manage",
    "observations.read", "reports.view", "reports.export", "access.review",
  ],
  health_data_analyst: [
    "children.read", "appointments.read", "growth.read", "vaccination.read",
    "nutrition.read", "alerts.read", "visits.read", "referrals.read", "observations.read", "reports.view", "reports.export",
  ],
  parent: [
    "children.read", "children.update", "appointments.read", "appointments.create",
    "growth.read", "vaccination.read", "nutrition.read", "alerts.read", "visits.read", "consent.record",
  ],
};

async function main() {
  console.log("🌱 Starting database seed for DRC...");

  // 1. Roles
  console.log("📋 Seeding roles...");
  const roles = [
    { code: "super_administrator", displayName: "Super Administrateur" },
    { code: "administrator", displayName: "Administrateur" },
    { code: "national_administrator", displayName: "Administrateur National" },
    { code: "provincial_administrator", displayName: "Administrateur Provincial" },
    { code: "health_zone_administrator", displayName: "Administrateur de Zone de Santé" },
    { code: "health_facility_administrator", displayName: "Administrateur de Formation Sanitaire" },
    { code: "health_professional", displayName: "Professionnel de Santé" },
    { code: "community_health_worker", displayName: "Agent de Santé Communautaire" },
    { code: "health_center_manager", displayName: "Gestionnaire de Centre de Santé" },
    { code: "health_supervisor", displayName: "Superviseur de Santé" },
    { code: "health_data_analyst", displayName: "Analyste de Données de Santé" },
    { code: "parent", displayName: "Parent/Tuteur" },
  ];
  for (const r of roles) {
    await prisma.role.upsert({ where: { code: r.code }, update: { displayName: r.displayName }, create: r });
  }

  // 2. Permissions
  console.log("🔐 Seeding permissions...");
  for (const p of PERMISSIONS) {
    await prisma.permission.upsert({ where: { code: p.code }, update: { description: p.description }, create: p });
  }

  // 3. Role-Permissions
  console.log("🔗 Seeding role-permissions...");
  for (const [roleCode, permCodes] of Object.entries(ROLE_PERMISSIONS)) {
    for (const permCode of permCodes) {
      await prisma.rolePermission.upsert({
        where: { roleCode_permissionCode: { roleCode, permissionCode: permCode } },
        update: {},
        create: { roleCode, permissionCode: permCode },
      });
    }
  }

  // 4. Provinces
  console.log("🗺️ Seeding provinces...");
  const provinceMap = new Map<string, any>();
  for (const prov of DRC_PROVINCES) {
    const p = await prisma.province.upsert({
      where: { code: prov.code },
      update: { name: prov.name },
      create: prov,
    });
    provinceMap.set(prov.code, p);
  }

  // 5. Health Zones
  console.log("🏥 Seeding health zones...");
  const zoneMap = new Map<string, any>();
  for (const [provCode, zones] of Object.entries(HEALTH_ZONES_BY_PROVINCE)) {
    const province = provinceMap.get(provCode);
    if (!province) continue;
    for (const zone of zones) {
      const z = await prisma.healthZone.upsert({
        where: { code: zone.code },
        update: { name: zone.name, provinceId: province.id },
        create: { code: zone.code, name: zone.name, provinceId: province.id },
      });
      zoneMap.set(zone.code, z);
    }
  }

  // 6. Health Areas
  console.log("📍 Seeding health areas...");
  const areaMap = new Map<string, any>();
  for (const [zoneCode, areas] of Object.entries(HEALTH_AREAS_BY_ZONE)) {
    const zone = zoneMap.get(zoneCode);
    if (!zone) continue;
    for (const area of areas) {
      const a = await prisma.healthArea.upsert({
        where: { code: area.code },
        update: { name: area.name, healthZoneId: zone.id },
        create: { code: area.code, name: area.name, healthZoneId: zone.id },
      });
      areaMap.set(area.code, a);
    }
  }

  // 7. Health Centers
  console.log("🏥 Seeding health centers...");
  let centerCount = 0;
  for (const [areaCode, centers] of Object.entries(HEALTH_CENTERS_BY_AREA)) {
    const area = areaMap.get(areaCode);
    if (!area) continue;
    const zone = zoneMap.get(areaCode.substring(0, 5)); // e.g., KIN01
    const province = provinceMap.get(areaCode.substring(0, 3)); // e.g., KIN
    for (const center of centers) {
      await prisma.healthCenter.upsert({
        where: { id: -1 },
        update: {
          name: center.name,
          facilityType: center.type,
          healthAreaId: area.id,
          healthZoneId: zone?.id,
          provinceId: province?.id,
          latitude: center.latitude,
          longitude: center.longitude,
          verificationStatus: "verified",
          facilityCode: center.code,
        },
        create: {
          facilityCode: center.code,
          name: center.name,
          facilityType: center.type,
          healthAreaId: area.id,
          healthZoneId: zone?.id,
          provinceId: province?.id,
          latitude: center.latitude,
          longitude: center.longitude,
          verificationStatus: "verified",
          source: "seed",
          sourceVersion: "1.0",
        },
      });
      centerCount++;
    }
  }
  console.log(`   Created ${centerCount} health centers`);

  // 8. Vaccines
  console.log("💉 Seeding vaccines...");
  const vaccineMap = new Map<string, any>();
  for (const v of VACCINES) {
    const vac = await prisma.vaccine.upsert({
      where: { vaccineCode: v.code },
      update: { displayName: v.name, description: v.description, isActive: true },
      create: { vaccineCode: v.code, displayName: v.name, description: v.description, isActive: true },
    });
    vaccineMap.set(v.code, vac);
  }

  // 9. Vaccine Doses
  console.log("💊 Seeding vaccine doses...");
  for (const [vaccineCode, doses] of Object.entries(DOSES_BY_VACCINE)) {
    const vaccine = vaccineMap.get(vaccineCode);
    if (!vaccine) continue;
    for (const dose of doses) {
      await prisma.vaccineDose.upsert({
        where: { vaccineId_doseCode: { vaccineId: vaccine.id, doseCode: dose.code } },
        update: { displayName: dose.name, doseOrder: dose.order },
        create: { vaccineId: vaccine.id, doseCode: dose.code, displayName: dose.name, doseOrder: dose.order },
      });
    }
  }

  // 10. Vaccination Schedules
  console.log("📅 Seeding vaccination schedules...");
  for (const sched of VACCINATION_SCHEDULE) {
    const schedule = await prisma.vaccinationSchedule.upsert({
      where: { scheduleCode_versionLabel: { scheduleCode: sched.code, versionLabel: sched.version } },
      update: { displayName: sched.name, jurisdictionCode: sched.jurisdiction, programName: sched.program, status: sched.status, effectiveFrom: sched.effectiveFrom },
      create: { scheduleCode: sched.code, displayName: sched.name, jurisdictionCode: sched.jurisdiction, programName: sched.program, versionLabel: sched.version, status: sched.status, effectiveFrom: sched.effectiveFrom },
    });

    // Schedule Doses
    const doseOrder = 1;
    for (const [vaccineCode, doses] of Object.entries(DOSES_BY_VACCINE)) {
      const vaccine = vaccineMap.get(vaccineCode);
      if (!vaccine) continue;
      for (const dose of doses) {
        const vaccineDose = await prisma.vaccineDose.findUnique({ where: { vaccineId_doseCode: { vaccineId: vaccine.id, doseCode: dose.code } } });
        if (!vaccineDose) continue;

        let intervalRefId: bigint | null = null;
        if (dose.intervalRef) {
          const refDose = await prisma.vaccineDose.findUnique({ where: { vaccineId_doseCode: { vaccineId: vaccine.id, doseCode: dose.intervalRef } } });
          if (refDose) intervalRefId = refDose.id;
        }

        await prisma.vaccinationScheduleDose.upsert({
          where: { scheduleId_vaccineDoseId: { scheduleId: schedule.id, vaccineDoseId: vaccineDose.id } },
          update: {
            earliestAgeDays: dose.earliestDays ?? null,
            recommendedAgeDays: dose.recommendedDays,
            latestAgeDays: dose.latestDays ?? null,
            minimumIntervalDays: dose.minIntervalDays ?? null,
            intervalReferenceDoseId: intervalRefId,
          },
          create: {
            scheduleId: schedule.id,
            vaccineDoseId: vaccineDose.id,
            earliestAgeDays: dose.earliestDays ?? null,
            recommendedAgeDays: dose.recommendedDays,
            latestAgeDays: dose.latestDays ?? null,
            minimumIntervalDays: dose.minIntervalDays ?? null,
            intervalReferenceDoseId: intervalRefId,
          },
        });
      }
    }
  }

  // 11. Admin User
  console.log("👤 Seeding admin user...");
  const passwordHash = await hash("Admin@123456", 12);
  const admin = await prisma.user.upsert({
    where: { username: "admin" },
    update: {},
    create: {
      displayName: "Administrateur Principal",
      username: "admin",
      email: "admin@afyamtoto.ai",
      passwordHash,
      isActive: true,
    },
  });
  await prisma.userRole.upsert({
    where: { userId_roleCode: { userId: admin.id, roleCode: "super_administrator" } },
    update: {},
    create: { userId: admin.id, roleCode: "super_administrator" },
  });

  // 12. Health Center Services (common services)
  console.log("🏥 Seeding health center services...");
  const services = [
    "Consultation prénatale", "Accouchement", "Soins néonataux", "Vaccination", "Croissance et développement",
    "Nutrition", "Planification familiale", "VIH/SIDA - Dépistage", "VIH/SIDA - Traitement (ARV)",
    "Tuberculose - Dépistage", "Tuberculose - Traitement", "Paludisme - Dépistage", "Paludisme - Traitement",
    "Malnutrition - Prise en charge", "Santé mentale", "Urgences", "Laboratoire", "Pharmacie",
    "Imagerie médicale", "Chirurgie mineure", "Soins dentaires", "Ophtalmologie", "Pédiatrie",
  ];
  const allCenters = await prisma.healthCenter.findMany({ select: { id: true } });
  for (const center of allCenters) {
    const centerServices = services.slice(0, 8 + Math.floor(Math.random() * 8));
    for (const svc of centerServices) {
      await prisma.healthCenterService.upsert({
        where: { healthCenterId_serviceName: { healthCenterId: center.id, serviceName: svc } },
        update: {},
        create: { healthCenterId: center.id, serviceName: svc },
      });
    }
  }

  console.log("✅ Seed completed successfully!");
}

main()
  .then(() => prisma.$disconnect())
  .catch((e) => { console.error(e); prisma.$disconnect(); process.exit(1); });