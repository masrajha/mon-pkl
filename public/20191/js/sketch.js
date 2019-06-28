var bandarlampung=null;
function setup() {
  createCanvas(600,400);
  background(0);
  bandarlampung=fitur.filter(isBDL);
  console.log(fitur);
  // console.log(bandarlampung);
}

function draw() {
  // put drawing code here
}

function isBDL(data){
  return (data.kota == 'Bandar Lampung');
}