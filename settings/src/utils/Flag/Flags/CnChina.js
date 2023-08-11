import * as React from "react";
const SvgCnChina = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="CN_-_China_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#CN_-_China_svg__a)">
      <path fill="#E31D1C" d="M0 0h16v12H0V0Z" />
      <path
        fill="#FECA00"
        d="M3.557 4.878 1.61 6.403l.744-2.307-1.299-1.2 1.758-.065.744-1.857.744 1.857h1.754L4.76 4.096l.59 2.307-1.793-1.525ZM7.508 3.086l-.817.493.187-.962-.68-.72.92-.04.39-.898.39.899h.92l-.68.759.205.962-.835-.493Z"
      />
      <path
        fill="#FECA00"
        d="m8.508 5.086-.817.493.187-.962-.68-.72.92-.04.39-.898.39.899h.92l-.68.759.205.962-.835-.493Z"
      />
      <path
        fill="#FECA00"
        d="m7.508 7.086-.817.493.187-.962-.68-.72.92-.04.39-.898.39.899h.92l-.68.759.205.962-.835-.493Z"
      />
      <path
        fill="#FECA00"
        d="m5.508 8.086-.817.493.187-.962-.68-.72.92-.04.39-.898.39.899h.92l-.68.759.205.962-.835-.493Z"
      />
    </g>
  </svg>
);
export default SvgCnChina;
