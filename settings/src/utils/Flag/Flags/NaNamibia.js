import * as React from "react";
const SvgNaNamibia = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="NA_-_Namibia_svg__a"
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
    <g mask="url(#NA_-_Namibia_svg__a)">
      <path
        fill="#093"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="NA_-_Namibia_svg__b"
        width={16}
        height={12}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0v12h16V0H0Z"
          clipRule="evenodd"
        />
      </mask>
      <g mask="url(#NA_-_Namibia_svg__b)">
        <path
          fill="#3195F9"
          fillRule="evenodd"
          d="M0 0v12L16 0H0Z"
          clipRule="evenodd"
        />
        <path
          fill="#E31D1C"
          stroke="#F7FCFF"
          d="m-.506 13.547.265.48.454-.308L17.629 1.864l.35-.24-.178-.384-1.171-2.52-.246-.528-.485.323-17.678 11.778-.382.255.222.403 1.433 2.596Z"
        />
        <path
          fill="#FECA00"
          fillRule="evenodd"
          d="m3.115 4.622-.647.808-.157-1.023-.964.376.376-.964L.7 3.662l.807-.647L.7 2.368l1.023-.157-.376-.964.964.376L2.468.6l.647.807L3.762.6l.157 1.023.964-.376-.376.964 1.023.157-.808.647.808.647-1.023.157.376.964-.964-.376-.157 1.023-.647-.808Zm0-.357a1.25 1.25 0 1 0 0-2.5 1.25 1.25 0 0 0 0 2.5Zm1-1.25a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"
          clipRule="evenodd"
        />
      </g>
    </g>
  </svg>
);
export default SvgNaNamibia;
