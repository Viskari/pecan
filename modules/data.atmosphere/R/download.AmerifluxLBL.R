##' Download Ameriflux LBL CSV files
##'
##' @name download.AmerifluxLBL
##' @title download.AmerifluxLBL
##' @export
##' @param site the Ameriflux ID of the site to be downloaded, used as file name prefix. 
##' The "SITE_ID" field in \href{http://ameriflux.lbl.gov/sites/site-list-and-pages/}{list of Ameriflux sites}
##' @param outfolder location on disk where outputs will be stored
##' @param start_date the start date of the data to be downloaded. Format is YYYY-MM-DD (will only use the year part of the date)
##' @param end_date the end date of the data to be downloaded. Format is YYYY-MM-DD (will only use the year part of the date)
##' @param overwrite should existing files be overwritten
##' @param verbose should the function be very verbose
##' 
##' @author Ankur Desai, based on download.Ameriflux.R by Josh Mantooth, Rob Kooper

download.AmerifluxLBL <- function(sitename, outfolder, start_date, end_date, overwrite=FALSE, verbose=FALSE, username="pecan", ...) {
  # get start/end year code works on whole years only
  
  require(lubridate) 
  require(PEcAn.utils)
  require(data.table)
  require(httr)
  
  site <- sub(".* \\((.*)\\)", "\\1", sitename)
  
  start_date <- as.POSIXlt(start_date, tz = "GMT")
  end_date <- as.POSIXlt(end_date, tz = "GMT")
  
  start_year <- year(start_date)
  end_year <- year(end_date)
  
  # make sure output folder exists
  if(!file.exists(outfolder)){
    dir.create(outfolder, showWarnings=FALSE, recursive=TRUE)
  }
  
  #need to query to get full file name #this is Ameriflux version
  url <- "http://wile.lbl.gov:8080/AmeriFlux/DataDownload.svc/datafileURLs"
  json_query <- paste0('{"username":"',username,'","siteList":["',site,'"],"intendedUse":"Research - Land model/Earth system model","description":"PEcAn download"}')
  result <- POST(url, body = json_query, encode = "json", add_headers("Content-Type" = "application/json"))
  link <- content(result)

  ftplink <- NULL
  if (length(link$dataURLsList) > 0) { ftplink <- link$dataURLsList[[1]]$URL } 
  
  #test to see that we got back a FTP
  if (is.null(ftplink)) {    logger.severe("Could not get information about", site, ".",
                                                     "Is this an AmerifluxLBL site?")}
  #get zip and csv filenames
  outfname <- strsplit(ftplink,'/')
  outfname <- outfname[[1]][length(outfname[[1]])]
  
  output_zip_file <- file.path(outfolder, outfname)
  file_timestep_hh = 'HH'
  file_timestep_hr = 'HR'
  file_timestep = file_timestep_hh
  
  endname <- strsplit(outfname,'_')
  endname <- endname[[1]][length(endname[[1]])]
  endname <- substr(endname,1,nchar(endname)-4)
  outcsvname <- paste0(substr(outfname,1,15),'_',file_timestep_hh,'_',endname,'.csv')
  output_csv_file <- file.path(outfolder, outcsvname)
  outcsvname_hr <- paste0(substr(outfname,1,15),'_',file_timestep_hr,'_',endname,'.csv')
  output_csv_file_hr <- file.path(outfolder, outcsvname_hr)
  
  download_file_flag <- TRUE
  extract_file_flag <- TRUE
  if (!overwrite && file.exists(output_zip_file)) {
    logger.debug("File '", output_zip_file, "' already exists, skipping download")
    download_file_flag <- FALSE 
  }
  if (!overwrite && file.exists(output_csv_file)) { 
    logger.debug("File '", output_csv_file, "' already exists, skipping extraction.")
    download_file_flag <- FALSE
    extract_file_flag <- FALSE
    file_timestep <- 'HH'
  } else {
    if (!overwrite && file.exists(output_csv_file_hr)) { 
      logger.debug("File '", output_csv_file_hr, "' already exists, skipping extraction.")
      download_file_flag <- FALSE 
      extract_file_flag <- FALSE
      file_timestep <- 'HR'
      outcsvname <- outcsvname_hr
      output_csv_file <- output_csv_file_hr
    }
  }
  
  if (download_file_flag) {
    extract_file_flag <- TRUE
    download.file(ftplink,output_zip_file)
    if (!file.exists(output_zip_file)) {
      logger.severe("FTP did not download ", output_zip_file, " from ",ftplink)
    }
  }
  if (extract_file_flag) {
    avail_file <- unzip(output_zip_file,list=TRUE)
    if (length(grep('HH',avail_file))>0) {
      file_timestep <- 'HH'
    } else {
      if (length(grep('HR',avail_file))>0) 
      {
        file_timestep <- 'HR'
        output_csv_file <- output_csv_file_hr
        outcsvname <- outcsvname_hr
      } else {
        logger.severe("Half-hourly or Hourly data file was not found in ",output_zip_file)
      }
    }
    unzip(output_zip_file,outcsvname,exdir=outfolder)
    if(!file.exists(output_csv_file)) {
      logger.severe("ZIP file ",output_zip_file," did not contain CSV file ",outcsvname)
    }
  }
  
  dbfilename <- paste0(substr(outfname,1,15),'_',file_timestep,'_',endname)
  
  #get start and end year of data from file
  firstline <- system(paste0("head -4 ",output_csv_file),intern=TRUE)
  firstline <- firstline[4]
  lastline <- system(paste0("tail -1 ",output_csv_file),intern=TRUE)
  
  firstdate_st <- paste0(substr(firstline,1,4),"-",substr(firstline,5,6),"-",substr(firstline,7,8)," ",substr(firstline,9,10),":",substr(firstline,11,12))
  firstdate <- as.POSIXlt(firstdate_st)
  lastdate_st <- paste0(substr(lastline,1,4),"-",substr(lastline,5,6),"-",substr(lastline,7,8)," ",substr(lastline,9,10),":",substr(lastline,11,12))
  lastdate <- as.POSIXlt(lastdate_st)
  
  syear <- year(firstdate)
  eyear <- year(lastdate)

  if (start_year>eyear) {logger.severe("Start_Year", start_year, "exceeds end of record ",eyear," for ",site)}
  if (end_year<syear) {logger.severe("End_Year", end_year, "precedes start of record ",syear," for ",site)}
  
  rows <- 1  
  results <- data.frame(file=character(rows), host=character(rows),
                        mimetype=character(rows), formatname=character(rows),
                        startdate=character(rows), enddate=character(rows),
                        dbfile.name = dbfilename,
                        stringsAsFactors = FALSE)
  
  row <- 1
  results$file[row] <- output_csv_file
  results$host[row] <- fqdn()
  results$startdate[row] <- firstdate_st
  results$enddate[row] <- lastdate_st
  results$mimetype[row] <- 'text/csv'
  results$formatname[row] <- 'AMERIFLUX_BASE_HH'
  
  # return list of files downloaded
  invisible(results)

}