#-------------------------------------------------------------------------------
# Copyright (c) 2012 University of Illinois, NCSA.
# All rights reserved. This program and the accompanying materials
# are made available under the terms of the 
# University of Illinois/NCSA Open Source License
# which accompanies this distribution, and is available at
# http://opensource.ncsa.illinois.edu/license.html
#-------------------------------------------------------------------------------


##-------------------------------------------------------------------------------------------------#
##' Convert MAAT output to netCDF
##'
##' Converts all output contained in a folder to netCDF.
##' @name model2netcdf.MAAT
##' @title Function to convert MAAT model output to standard netCDF format
##' @param outdir Location of MAAT model output
##' @param sitelat Latitude of the site
##' @param sitelon Longitude of the site
##' @param start_date Start time of the simulation
##' @param end_date End time of the simulation
##' @export
##' @author Shawn Serbin, Anthony Walker
model2netcdf.MAAT <- function(outdir, sitelat=-999, sitelon=-999, start_date=NULL, end_date=NULL) {
  
  ## TODO is it OK to give site lat/long -999 if not running at a "site"?
  ## TODO  !!UPDATE SO IT WILL WORK WITH NO MET AND WITH MET DRIVER!!
  
  ### Load required libraries
  #require(PEcAn.utils) #nescessary??
  require(lubridate)
  require(udunits2)
  require(ncdf4)
  
  ### Read in model output in SIPNET format
  maat.out.file <- file.path(outdir, "out.csv")
  maat.output <- read.csv(maat.out.file, header=T)
  maat.output.dims <- dim(maat.output)

  ### Determine number of years and output timestep
  days <- as.Date(start_date):as.Date(end_date)
  year <- strftime(as.Date(days,origin="1970-01-01"), "%Y")
  num.years <- length(unique(year))
  maat.dates <- as.Date(maat.output$time, format = "%m/%d/%y")
  dims <- dim(subset(maat.output, as.Date(time, format = "%m/%d/%y") == seq(as.Date(start_date),
                                                                            by='days',length=1)))
  timestep.s <- 86400 / dims[1]

  ### Setup outputs for netCDF file in appropriate units
  for (y in unique(year)){
    if (file.exists(file.path(outdir, paste(y,"nc", sep=".")))) {
      next ## skip, model output already present.
    }
    
    print(paste("---- Processing year: ", y))  # turn on for debugging
    
    ## Subset data for processing
    sub.maat.output <- subset(maat.output, format(maat.dates, "%Y") == y)
    sub.maat.dates <- as.Date(sub.maat.output$time, format = "%m/%d/%y")
    sub.maat.doy <- yday(sub.maat.dates)
    sub.maat.output.dims <- dim(sub.maat.output)
    dayfrac <- 1 / dims[1]
    day.steps <- seq(0, 0.99, 1 / dims[1])

    ### standard variables: Carbon Pools [not currently relevant to MAAT]
    output <- list()                              # create empty output
    out.year <- as.numeric(rep(y,sub.maat.output.dims[1]))
    output[[1]] <- out.year                       # Simulation year
    output[[2]] <- sub.maat.doy+day.steps         # Fractional day - NEED TO IMPLEMENT  
    output[[3]] <- (sub.maat.output$A)            # assimilation in umolsC/m2/s
    output[[4]] <- (sub.maat.output$gs)           # stomatal conductance in mol H2O m-2 s-1
    
    ## !!TODO: ADD MORE MAAT OUTPUTS HERE!! ##
    
    #******************** Declare netCDF variables ********************#
    ## This version doesn't provide enough output timesteps when running with met data that has
    ## a step greater than 1 per day
    #t <- ncdim_def(name = "time",
    #               units = paste0("days since ", y, "-01-01 00:00:00"),
    #               vals = as.numeric(strptime(end_date, "%Y-%m-%d %H:%M:%S")-strptime(start_date, "%Y-%m-%d %H:%M:%S"),units="days"),
    #               calendar = "standard", unlim = TRUE) # is this correct? fraction of days or whole days
   
    ## Something like this works for mult timesteps per day
    t <- ncdim_def(name = "time",
                   units = paste0("days since ", y, "-01-01 00:00:00"),
                   vals = sub.maat.doy+day.steps,
                   calendar = "standard", unlim = TRUE)
    lat <- ncdim_def("lat", "degrees_east",vals =  as.numeric(sitelat),
                   longname = "station_latitude") 
    lon <- ncdim_def("lon", "degrees_north",vals = as.numeric(sitelon),
                   longname = "station_longitude")
    
    for(i in 1:length(output)){
      if(length(output[[i]])==0) output[[i]] <- rep(-999,length(t$vals))
    }
    
    ############ Variable Conversions
    ### Conversion factor for umol C -> kg C
    Mc <- 12.017 # molar mass of C, g/mol
    umol2kg_C <- Mc * ud.convert(1, "umol", "mol") * ud.convert(1, "g", "kg")
    
    ### Conversion factor for mol H2O -> kg H2O
    Mw <- 18.01528  # molar mass of H2O, g/mol
    mol2kg_H2O <- Mw * ud.convert(1, "g", "kg")
    ############ 
    
    ### Find/replace missing and convert outputs to standardized BETYdb units
    output[[3]] <- ifelse(output[[3]]==-999,-999,output[[3]]*umol2kg_C)  # convert A/GPP to kgC/m2/s
    #output[[4]] <- ifelse(output[[4]]=="Inf",-999,output[[4]]) # gs in mol H2O m-2 s-1
    output[[4]] <- ifelse(output[[4]]=="Inf",-999,output[[4]]*mol2kg_H2O) # stomatal_conductance in kg H2O m2 s1
    
    ### Put output into netCDF format
    mstmipvar <- PEcAn.utils::mstmipvar
    var <- list()
    var[[1]]  <- mstmipvar("Year", lat, lon, t, NA)
    var[[2]]  <- mstmipvar("FracJulianDay", lat, lon, t, NA)
    var[[3]]  <- mstmipvar("GPP", lat, lon, t, NA)
    var[[4]]  <- mstmipvar("stomatal_conductance", lat, lon, t, NA)
    
    ### Output netCDF data
    nc <- nc_create(file.path(outdir, paste(y,"nc", sep=".")), var)
    varfile <- file(file.path(outdir, paste(y, "nc", "var", sep=".")), "w")
    for(i in 1:length(var)){
      print(i) # just on for debugging
      ncvar_put(nc,var[[i]],output[[i]])  
      cat(paste(var[[i]]$name, var[[i]]$longname), file=varfile, sep="\n")
    } ## netCDF loop
    close(varfile)
    nc_close(nc)
    
  } ## Year loop
} ## Main loop
##-------------------------------------------------------------------------------------------------#
