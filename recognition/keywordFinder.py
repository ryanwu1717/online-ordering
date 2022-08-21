from thefuzz import fuzz
import numpy as np

coatingKeys = ['CrN', 'DLC', 'CVD', 'TiN', 'TICN', 'TiAlN', 'AlCrN', 'Plating', 'Nitriding', 'Black Oxide', 'Without Coating', 'Flame Hardening']
materialKeys = ['D2', 'M2', 'M4', 'M7', 'S7', 'V4', 'G10', 'G15', 'G20', 'G30', 'G40', 'G50', 'G55', 'H13', 'M35', 'M42', 'T15', 
'4140', '4340', '8620', 'K340', 'K890', 'S45C', 'S390', 'SKH9', 'SUJ2', 'W360', 
'ASP23', 'ASP30', 'ASP60', 'Brass', 'HAP10', 'SKD11', 'SKD61', 'SKH51', 'SKH55', 'SKH59', 
'1.2343', '1.2344', '1.2365', '1.2367', '1.2379', '1.2842', '1.3243', '1.3247', '1.3343', 
'AS DWG', 'CPM-3V', 'CPM-M4', 'MiL-60', 'SCM415', 'SCM435', 'SCM440', 'SUS303', 'SUS304', 'SUS316', 'CPM-10V', 
'Carbide', 'MiL-60R', 'MiL-60S', 'MiL-TIP', 'SNCM220', 'STELITE', 'Aluminum', 'SUS420J1', 'SUS420J2', 'red copper', 
'C3604 Brass', 'M42-MiL-60S', 'Aluminum bar', 
'C1100 copper', 'C83600 bronze', 'C90700 bronze', 'C93210 bronze', 'C95400 bronze', 'C95500 bronze', 'C95800 bronze', 'C95810 bronze', 
'CuCrZr copper', 'SAE841 Bronze', 'CAC502C Bronze', 'Solide Carbride', 'JIS-C-5191 bronze', 'JISCAC406C bronze', 'customer provided', 
'10% cobalt carbide', '11% cobalt carbide', '15% cobalt carbide', '20% cobalt carbide', '25% cobalt carbide', 'oxygen-free copper', 
'SAE64(C93700 BRONZE)', 'C17200 Beryllium Copper', 'CuSn6 bronze(C51900 BRONZE)', 'SAE660 Bronze(C93200 BRONZE)']
materialKeys_single = ['D2', 'M2', 'M4', 'M7', 'S7', 'V4', 'G10', 'G15', 'G20', 'G30', 'G40', 'G50', 'G55', 'H13', 'M35', 'M42', 'T15', 
'4140', '4340', '8620', 'K340', 'K890', 'S45C', 'S390', 'SKH9', 'SUJ2', 'W360', 
'ASP23', 'ASP30', 'ASP60', 'Brass', 'HAP10', 'SKD11', 'SKD61', 'SKH51', 'SKH55', 'SKH59', 
'1.2343', '1.2344', '1.2365', '1.2367', '1.2379', '1.2842', '1.3243', '1.3247', '1.3343', 
'AS DWG', 'CPM-3V', 'CPM-M4', 'MiL-60', 'SCM415', 'SCM435', 'SCM440', 'SUS303', 'SUS304', 'SUS316', 'CPM-10V', 
'Carbide', 'MiL-60R', 'MiL-60S', 'MiL-TIP', 'SNCM220', 'STELITE', 'Aluminum', 'SUS420J1', 'SUS420J2', 'red copper', 
'C3604', 'M42-MiL-60S', 'Aluminum bar', 'C1100', 'C83600', 'C90700', 'C93210', 'C95400', 'C95500', 'C95800', 'C95810', 
'CuCrZr', 'SAE841', 'CAC502C', 'Solide', 'C5191', 'CAC406C', 'customer provided', 
'oxygen-free', 'SAE64','C93700', 'C17200', 'CuSn6','C51900', 'SAE660', 'C93200']
material_alias = {'SAE64':'SAE64(C93700 BRONZE)','C93700':'SAE64(C93700 BRONZE)','CuSn6':'CuSn6 bronze(C51900 BRONZE)',
'C51900':'CuSn6 bronze(C51900 BRONZE)','SAE660':'SAE660 Bronze(C93200 BRONZE)','C93200':'SAE660 Bronze(C93200 BRONZE)',
'Solide':'Solide Carbride','cobalt':'cobalt carbide','oxygen-free':'oxygen-free copper'}
material_with_percentage = {'cobalt','co'}
poss_percentage = {'10','11','15','20','25'}

material_lang = ["material","werkstoff"]
coating_lang = ["coat","coating"]

def getPossibleMaterialsCoatings(wordlist,Height,Width,minfuzz):
	matIdx = []
	coatIdx = []
	wordlist.sort(key = lambda x:x[0][0])
	wordlist.sort(key = lambda x:x[0][1])
	for idx,word in enumerate(wordlist):
		for materiallit in material_lang:
			currratio = fuzz.ratio(word[1],materiallit)
			if(currratio > minfuzz):
				matIdx.append(idx)
				break
		for coatinglit in coating_lang:
			currratio = fuzz.ratio(word[1],coatinglit)
			if(currratio > minfuzz-20):
				coatIdx.append(idx)
				break
	possibleMaterial = []
	mats = []
	for matIndex in matIdx:
		matTL = np.array(wordlist[matIndex][0][0:2])
		mats.append(wordlist[matIndex])
		for idx,word in enumerate(wordlist):
			if(abs(word[0][0] - matTL[0]) < Width and abs(word[0][1] - matTL[1]) < Height):
				for matkey in materialKeys_single:
					prob = fuzz.token_sort_ratio(matkey.lower(),word[1])
					if(prob > minfuzz-20):
						if(matkey not in material_alias):
							possibleMaterial.append([int(prob)]+word+[matkey])
						else:
							possibleMaterial.append([int(prob)]+word+[material_alias[matkey]])
						break
				for matkey in material_with_percentage:
					prob = fuzz.token_sort_ratio(matkey.lower(),word[1])
					if(prob > minfuzz-20):
						perc_word = wordlist[idx-1]
						"""
						best_perc_score = 0
						best_perc = ""
						for perc in poss_percentage:
							if(fuzz.partial_ratio(perc,perc_word[1]) > best_perc_score):
								best_perc = perc
						""" #Using fixed percentage might not be a good idea, on hold
						possibleMaterial.append([int(prob)]+word+[perc_word[1][:2]+"% "+matkey])
						break
	possibleMaterial.sort(reverse = True,key = lambda X:X[0])
	possibleCoating = []
	coats = []
	for coatIndex in coatIdx:
		coatTL = np.array(wordlist[coatIndex][0][0:2])
		coats.append(wordlist[coatIndex])
		for idx,word in enumerate(wordlist):
			if(idx in coatIdx or idx in matIdx):
				continue
			if(abs(word[0][0] - coatTL[0]) < Width and abs(word[0][1] - coatTL[1]) < Height):
				for coatKey in coatingKeys:
					prob = fuzz.token_sort_ratio(coatKey.lower(),word[1])
					if(prob > minfuzz):
						possibleCoating.append([int(prob)]+word+[coatKey])
						break
	possibleCoating.sort(reverse = True,key = lambda X:X[0])
	return possibleMaterial,possibleCoating,mats,coats